/**
 * API Keys Persistence Manager
 * Handles auto-save and auto-load of API keys from server
 */

class APIKeysPersistence {
    constructor() {
        this.saveEndpoint = '/save_api_keys.php';
        this.loadEndpoint = '/load_api_keys.php';
        this.autoSaveDelay = 2000; // 2 seconds after input
        this.saveTimeout = null;
    }

    /**
     * Load API keys from server on page load
     */
    async loadKeys() {
        try {
            const response = await fetch(this.loadEndpoint, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                console.log('No saved API keys found or user not logged in');
                return null;
            }

            const result = await response.json();
            
            if (result.success && result.data) {
                // Populate localStorage with multi-key arrays
                if (result.data.gemini_keys && Array.isArray(result.data.gemini_keys)) {
                    localStorage.setItem('gemini_keys_secure', JSON.stringify(result.data.gemini_keys));
                    // Also set single key for backward compatibility
                    if (result.data.gemini_keys.length > 0) {
                        localStorage.setItem('gemini_api_key', result.data.gemini_keys[0]);
                    }
                } else if (result.data.gemini_key) {
                    localStorage.setItem('gemini_api_key', result.data.gemini_key);
                }
                
                if (result.data.youtube_keys && Array.isArray(result.data.youtube_keys)) {
                    localStorage.setItem('youtube_keys_secure', JSON.stringify(result.data.youtube_keys));
                    // Also set single key for backward compatibility
                    if (result.data.youtube_keys.length > 0) {
                        localStorage.setItem('youtube_api_key', result.data.youtube_keys[0]);
                    }
                } else if (result.data.youtube_key) {
                    localStorage.setItem('youtube_api_key', result.data.youtube_key);
                }
                
                if (result.data.openrouter_keys && Array.isArray(result.data.openrouter_keys)) {
                    localStorage.setItem('openrouter_keys_secure', JSON.stringify(result.data.openrouter_keys));
                    // Also set single key for backward compatibility
                    if (result.data.openrouter_keys.length > 0) {
                        localStorage.setItem('openrouter_api_key', result.data.openrouter_keys[0]);
                    }
                } else if (result.data.openrouter_key) {
                    localStorage.setItem('openrouter_api_key', result.data.openrouter_key);
                }
                
                if (result.data.openai_key) {
                    localStorage.setItem('openai_api_key', result.data.openai_key);
                }

                console.log('✅ API keys loaded from server:', result.data);
                return result.data;
            }
        } catch (error) {
            console.error('Failed to load API keys:', error);
        }
        return null;
    }

    /**
     * Save API keys to server
     */
    async saveKeys(keys) {
        try {
            const response = await fetch(this.saveEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(keys)
            });

            const result = await response.json();
            
            if (result.success) {
                console.log('✅ API keys saved to server');
                return true;
            } else {
                console.error('Failed to save API keys:', result.error);
                return false;
            }
        } catch (error) {
            console.error('Failed to save API keys:', error);
            return false;
        }
    }

    /**
     * Auto-save keys when input changes
     */
    setupAutoSave(inputSelectors) {
        inputSelectors.forEach(selector => {
            const input = document.querySelector(selector);
            if (input) {
                input.addEventListener('input', () => {
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => {
                        this.saveCurrentKeys();
                    }, this.autoSaveDelay);
                });

                // Also save on blur
                input.addEventListener('blur', () => {
                    this.saveCurrentKeys();
                });
            }
        });
    }

    /**
     * Save current keys from localStorage (support multi-key arrays)
     */
    async saveCurrentKeys() {
        // Try to load multi-key arrays first
        const geminiKeysRaw = localStorage.getItem('gemini_keys_secure');
        const youtubeKeysRaw = localStorage.getItem('youtube_keys_secure');
        const openrouterKeysRaw = localStorage.getItem('openrouter_keys_secure');
        
        let geminiKeys = [];
        let youtubeKeys = [];
        let openrouterKeys = [];
        
        // Parse multi-key arrays
        if (geminiKeysRaw) {
            try {
                geminiKeys = JSON.parse(geminiKeysRaw);
            } catch(e) {
                geminiKeys = localStorage.getItem('gemini_api_key') || '';
            }
        } else {
            geminiKeys = localStorage.getItem('gemini_api_key') || '';
        }
        
        if (youtubeKeysRaw) {
            try {
                youtubeKeys = JSON.parse(youtubeKeysRaw);
            } catch(e) {
                youtubeKeys = localStorage.getItem('youtube_api_key') || '';
            }
        } else {
            youtubeKeys = localStorage.getItem('youtube_api_key') || '';
        }
        
        if (openrouterKeysRaw) {
            try {
                openrouterKeys = JSON.parse(openrouterKeysRaw);
            } catch(e) {
                openrouterKeys = localStorage.getItem('openrouter_api_key') || '';
            }
        } else {
            openrouterKeys = localStorage.getItem('openrouter_api_key') || '';
        }
        
        const keys = {
            gemini_keys: geminiKeys,
            youtube_keys: youtubeKeys,
            openrouter_keys: openrouterKeys,
            openai_key: localStorage.getItem('openai_api_key') || ''
        };

        // Only save if at least one key exists
        const hasKeys = (Array.isArray(geminiKeys) && geminiKeys.length > 0) || 
                       (Array.isArray(youtubeKeys) && youtubeKeys.length > 0) || 
                       (Array.isArray(openrouterKeys) && openrouterKeys.length > 0) || 
                       keys.openai_key !== '';
        
        if (hasKeys) {
            await this.saveKeys(keys);
        }
    }

    /**
     * Sync input fields with localStorage
     */
    syncInputFields(inputMap) {
        Object.entries(inputMap).forEach(([storageKey, inputSelector]) => {
            const value = localStorage.getItem(storageKey);
            const input = document.querySelector(inputSelector);
            
            if (input && value) {
                input.value = value;
                // Trigger change event for any listeners
                input.dispatchEvent(new Event('change'));
            }
        });
    }
}

// Global instance
window.apiKeysPersistence = new APIKeysPersistence();

// Auto-load on page load
document.addEventListener('DOMContentLoaded', async () => {
    await window.apiKeysPersistence.loadKeys();
});
