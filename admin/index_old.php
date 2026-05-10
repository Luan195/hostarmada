<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('../login.php');
}

$users = loadDB('users.json');
$keys = loadDB('keys.json');
$orders = loadDB('orders.json');

$totalUsers = count($users);
$vipUsers = count(array_filter($users, fn($u) => $u['tier'] === TIER_VIP));
$basicUsers = count(array_filter($users, fn($u) => $u['tier'] === TIER_BASIC));
$freeUsers = count(array_filter($users, fn($u) => $u['tier'] === TIER_FREE));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100">

    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white p-6">
            <h1 class="text-2xl font-black mb-8"><i class="fa-solid fa-shield-halved text-red-500 mr-2"></i> ADMIN</h1>
            <nav class="space-y-2">
                <a href="index.php" class="block bg-red-600 text-white px-4 py-2 rounded-lg font-bold"><i class="fa-solid fa-dashboard mr-2"></i> Dashboard</a>
                <a href="keys.php" class="block text-slate-300 hover:bg-slate-800 px-4 py-2 rounded-lg"><i class="fa-solid fa-key mr-2"></i> API Keys</a>
                <a href="users.php" class="block text-slate-300 hover:bg-slate-800 px-4 py-2 rounded-lg"><i class="fa-solid fa-users mr-2"></i> Users</a>
                <a href="../scanner.php" class="block text-slate-300 hover:bg-slate-800 px-4 py-2 rounded-lg"><i class="fa-solid fa-radar mr-2"></i> Go to Scanner</a>
                <a href="../logout.php" class="block text-red-400 hover:bg-slate-800 px-4 py-2 rounded-lg mt-8"><i class="fa-solid fa-sign-out-alt mr-2"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-black mb-8">System Overview</h2>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-slate-600">Total Users</h3>
                        <i class="fa-solid fa-users text-2xl text-blue-500"></i>
                    </div>
                    <p class="text-3xl font-black"><?php echo $totalUsers; ?></p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-slate-600">VIP Users</h3>
                        <i class="fa-solid fa-crown text-2xl text-yellow-500"></i>
                    </div>
                    <p class="text-3xl font-black"><?php echo $vipUsers; ?></p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-slate-600">Basic Users</h3>
                        <i class="fa-solid fa-star text-2xl text-blue-500"></i>
                    </div>
                    <p class="text-3xl font-black"><?php echo $basicUsers; ?></p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-slate-600">Free Users</h3>
                        <i class="fa-solid fa-user text-2xl text-slate-400"></i>
                    </div>
                    <p class="text-3xl font-black"><?php echo $freeUsers; ?></p>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-xl mb-4">Recent Users</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b">
                            <tr>
                                <th class="pb-2 font-bold text-slate-600">Username</th>
                                <th class="pb-2 font-bold text-slate-600">Email</th>
                                <th class="pb-2 font-bold text-slate-600">Tier</th>
                                <th class="pb-2 font-bold text-slate-600">Joined</th>
                                <th class="pb-2 font-bold text-slate-600">Last Login</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php
                            $sortedUsers = $users;
                            usort($sortedUsers, fn($a, $b) => strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0));
                            $recentUsers = array_slice($sortedUsers, 0, 10);
                            
                            foreach ($recentUsers as $user):
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="py-3 text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3"><?php echo getTierBadge($user['tier']); ?></td>
                                <td class="py-3 text-sm text-slate-600"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td class="py-3 text-sm text-slate-600"><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <style>
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: bold; }
        .bg-secondary { background-color: #6c757d; color: white; }
        .bg-primary { background-color: #0d6efd; color: white; }
        .bg-warning { background-color: #ffc107; color: #000; }
        .bg-danger { background-color: #dc3545; color: white; }
    </style>

</body>
</html>
