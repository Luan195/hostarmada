<?php
/**
 * API Key Pool Manager
 * Handles tier-based API key allocation strategy
 * 
 * TIER STRATEGY:
 * - FREE: Blocked (no API access)
 * - TRIAL: Admin's shared YouTube API keys (limited quota)
 * - BASIC: User's own API key (self-service)
 * - PREMIUM: Admin's OpenRouter API (unlimited)
 */

require_once __DIR__ . '/config.php';

class APIKeyPool {
    private $keysFile = __DIR__ . '/../data/admin_api_keys.json';
    
    /**
     * Get API key based on user tier
     * 
     * @param string $userTier User's tier level
     * @param string|null $userAPIKey User's own API key (if provided)
     * @return array ['key' => string, 'provider' => string, 'quota_limited' => bool]
     */
    public function getAPIKey($userTier, $userAPIKey = null) {
        switch ($userTier) {
            case TIER_FREE:
                // FREE users: Blocked
                return [
                    'key' => null,
                    'provider' => 'blocked',
                    'quota_limited' => true,
                    'error' => 'Free tier không được phép tìm kiếm'
                ];
                
            case TIER_TRIAL:
                // TRIAL users: Use admin's shared YouTube API keys
                // If user provided own key, use it for better performance
                if (!empty($userAPIKey)) {
                    return [
                        'key' => $userAPIKey,
                        'provider' => 'user_youtube',
                        'quota_limited' => true,
                        'note' => 'Using your own API key'
                    ];
                }
                
                // Otherwise use admin's shared pool
                $sharedKey = $this->getSharedYouTubeKey();
                return [
                    'key' => $sharedKey['key'],
                    'provider' => 'admin_youtube_shared',
                    'quota_limited' => true,
                    'note' => 'Using shared API (may be slower during peak hours)'
                ];
                
            case TIER_BASIC:
                // BASIC users: MUST use own API key
                if (empty($userAPIKey)) {
                    return [
                        'key' => null,
                        'provider' => 'none',
                        'quota_limited' => true,
                        'error' => 'Basic tier requires your own YouTube API key. Please add it in Settings.'
                    ];
                }
                
                return [
                    'key' => $userAPIKey,
                    'provider' => 'user_youtube',
                    'quota_limited' => true,
                    'note' => 'Using your own API key'
                ];
                
            case TIER_VIP:
            case 'premium':
                // PREMIUM users: Use admin's OpenRouter (unlimited)
                $openRouterKey = $this->getOpenRouterKey();
                
                if (!empty($openRouterKey)) {
                    return [
                        'key' => $openRouterKey,
                        'provider' => 'admin_openrouter',
                        'quota_limited' => false,
                        'note' => '🌟 Premium: Unlimited API access via OpenRouter'
                    ];
                }
                
                // Fallback: If admin hasn't set up OpenRouter yet
                if (!empty($userAPIKey)) {
                    return [
                        'key' => $userAPIKey,
                        'provider' => 'user_youtube',
                        'quota_limited' => true,
                        'note' => 'Using your own API key (OpenRouter not configured)'
                    ];
                }
                
                return [
                    'key' => null,
                    'provider' => 'none',
                    'quota_limited' => true,
                    'error' => 'Premium API pool not configured. Please contact admin or add your own key.'
                ];
                
            default:
                return [
                    'key' => null,
                    'provider' => 'unknown',
                    'quota_limited' => true,
                    'error' => 'Invalid tier'
                ];
        }
    }
    
    /**
     * Get next available shared YouTube API key (round-robin)
     */
    private function getSharedYouTubeKey() {
        $keys = $this->loadAdminKeys();
        
        if (empty($keys['youtube_shared'])) {
            return [
                'key' => null,
                'error' => 'No shared YouTube API keys configured'
            ];
        }
        
        // Simple round-robin: Find key with lowest usage
        $bestKey = null;
        $minUsage = PHP_INT_MAX;
        
        foreach ($keys['youtube_shared'] as &$keyData) {
            if ($keyData['status'] === 'active' && $keyData['usage_today'] < $keyData['daily_limit']) {
                if ($keyData['usage_today'] < $minUsage) {
                    $minUsage = $keyData['usage_today'];
                    $bestKey = &$keyData;
                }
            }
        }
        
        if ($bestKey === null) {
            return [
                'key' => null,
                'error' => 'All shared API keys exhausted. Please try again tomorrow or upgrade to Basic/Premium.'
            ];
        }
        
        // Increment usage
        $bestKey['usage_today']++;
        $this->saveAdminKeys($keys);
        
        return [
            'key' => $bestKey['key'],
            'usage' => $bestKey['usage_today'],
            'limit' => $bestKey['daily_limit']
        ];
    }
    
    /**
     * Get OpenRouter API key for Premium users
     */
    private function getOpenRouterKey() {
        $keys = $this->loadAdminKeys();
        
        if (!empty($keys['openrouter']['key']) && $keys['openrouter']['status'] === 'active') {
            return $keys['openrouter']['key'];
        }
        
        return null;
    }
    
    /**
     * Load admin API keys from JSON
     */
    private function loadAdminKeys() {
        if (!file_exists($this->keysFile)) {
            return $this->getDefaultKeys();
        }
        
        $data = json_decode(file_get_contents($this->keysFile), true);
        return $data ?: $this->getDefaultKeys();
    }
    
    /**
     * Save admin API keys to JSON
     */
    private function saveAdminKeys($keys) {
        $dir = dirname($this->keysFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->keysFile, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Get default keys structure
     */
    private function getDefaultKeys() {
        return [
            'youtube_shared' => [
                // Admin can add multiple YouTube API keys here
                // Example:
                // [
                //     'key' => 'AIzaSyA...',
                //     'name' => 'Shared Key 1',
                //     'status' => 'active',
                //     'daily_limit' => 1500,
                //     'usage_today' => 0,
                //     'reset_at' => date('Y-m-d') . ' 00:00:00'
                // ]
            ],
            'openrouter' => [
                'key' => '', // Admin adds OpenRouter key here
                'status' => 'inactive',
                'model' => 'google/gemini-2.0-flash-exp:free' // Use free model
            ]
        ];
    }
    
    /**
     * Reset daily usage counters (should be run via cron daily)
     */
    public function resetDailyUsage() {
        $keys = $this->loadAdminKeys();
        $today = date('Y-m-d');
        
        foreach ($keys['youtube_shared'] as &$keyData) {
            $resetDate = date('Y-m-d', strtotime($keyData['reset_at']));
            
            if ($resetDate < $today) {
                $keyData['usage_today'] = 0;
                $keyData['reset_at'] = $today . ' 00:00:00';
            }
        }
        
        $this->saveAdminKeys($keys);
    }
}
