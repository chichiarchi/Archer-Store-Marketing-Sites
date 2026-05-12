<?php
require_once 'db.php';

$message = '';
$messageType = 'success';
$activeTab = $_GET['tab'] ?? 'settings';

// Helper for Image Uploads
function handleUpload($fileKey) {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/images/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileInfo = pathinfo($_FILES[$fileKey]['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($extension, $allowed)) {
            $newFilename = $fileKey . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $newFilename;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $destination)) {
                return $newFilename;
            }
        }
    }
    return null;
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_settings':
                    $settings = ['store_name', 'hero_title', 'hero_subtitle', 'about_title', 'about_text', 'phone_number', 'email'];
                    $stmt = $pdo->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = ?");
                    foreach ($settings as $key) {
                        if (isset($_POST[$key])) $stmt->execute([$_POST[$key], $key]);
                    }
                    $heroImg = handleUpload('hero_image');
                    if ($heroImg) $pdo->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'hero_image'")->execute([$heroImg]);
                    $aboutImg = handleUpload('about_image');
                    if ($aboutImg) $pdo->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'about_image'")->execute([$aboutImg]);
                    $message = "Settings updated!";
                    break;

                case 'add_social':
                    $stmt = $pdo->prepare("INSERT INTO social_links (platform, url) VALUES (?, ?)");
                    $stmt->execute([$_POST['platform'], $_POST['url']]);
                    $message = "Social link added!";
                    break;

                case 'delete_social':
                    $pdo->prepare("DELETE FROM social_links WHERE id = ?")->execute([$_POST['id']]);
                    $message = "Social link removed!";
                    break;

                case 'add_promo':
                    $image = handleUpload('promo_image') ?? 'promo.webp';
                    $stmt = $pdo->prepare("INSERT INTO promos (title, description, image_url, valid_until, is_active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['title'], $_POST['description'], $image, $_POST['valid_until'], 1]);
                    $message = "Promo added!";
                    break;

                case 'edit_promo':
                    $image = handleUpload('promo_image');
                    if ($image) {
                        $stmt = $pdo->prepare("UPDATE promos SET title = ?, description = ?, image_url = ?, valid_until = ? WHERE id = ?");
                        $stmt->execute([$_POST['title'], $_POST['description'], $image, $_POST['valid_until'], $_POST['id']]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE promos SET title = ?, description = ?, valid_until = ? WHERE id = ?");
                        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['valid_until'], $_POST['id']]);
                    }
                    $message = "Promo updated!";
                    break;

                case 'delete_promo':
                    $pdo->prepare("DELETE FROM promos WHERE id = ?")->execute([$_POST['id']]);
                    $message = "Promo deleted!";
                    break;

                case 'update_schedule':
                    $stmt = $pdo->prepare("UPDATE store_schedule SET is_open = ?, open_time = ?, close_time = ? WHERE day_of_week = ?");
                    for ($i = 0; $i <= 6; $i++) {
                        $is_open = isset($_POST["is_open_$i"]) ? 1 : 0;
                        $stmt->execute([$is_open, $_POST["open_time_$i"], $_POST["close_time_$i"], $i]);
                    }
                    $message = "Schedule updated!";
                    break;

                case 'add_branch':
                    $address = $_POST['address'];
                    $embed = $_POST['google_maps_embed'];
                    if (empty($embed)) {
                        $encodedAddr = urlencode($address);
                        $embed = "<iframe width='100%' height='300' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='https://maps.google.com/maps?q=$encodedAddr&t=&z=13&ie=UTF8&iwloc=&output=embed'></iframe>";
                    }
                    $stmt = $pdo->prepare("INSERT INTO branches (name, address, google_maps_embed, is_active) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_POST['name'], $address, $embed, 1]);
                    $message = "Branch added!";
                    break;

                case 'delete_branch':
                    $pdo->prepare("DELETE FROM branches WHERE id = ?")->execute([$_POST['id']]);
                    $message = "Branch removed!";
                    break;
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch Data
$settingsData = $pdo->query("SELECT setting_key, setting_value FROM store_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$socialLinks = $pdo->query("SELECT * FROM social_links")->fetchAll();
$schedule = $pdo->query("SELECT * FROM store_schedule ORDER BY day_of_week")->fetchAll();
$promos = $pdo->query("SELECT * FROM promos ORDER BY id DESC")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches ORDER BY id DESC")->fetchAll();

$days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archer Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .active-tab { background: #1e293b; border-right: 4px solid #3b82f6; color: white; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 h-screen bg-[#0f172a] text-slate-400 flex flex-col fixed left-0 top-0 z-50">
            <div class="p-6 text-white text-2xl font-bold flex items-center">
                <i class="fa-solid fa-leaf text-green-500 mr-2"></i> Archer Admin
            </div>
            <nav class="flex-1 px-4 space-y-1">
                <a href="?tab=settings" class="flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 hover:text-white transition-all <?= $activeTab === 'settings' ? 'active-tab' : '' ?>">
                    <i class="fa-solid fa-gear w-6"></i> Settings
                </a>
                <a href="?tab=schedule" class="flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 hover:text-white transition-all <?= $activeTab === 'schedule' ? 'active-tab' : '' ?>">
                    <i class="fa-solid fa-clock w-6"></i> Schedule
                </a>
                <a href="?tab=promos" class="flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 hover:text-white transition-all <?= $activeTab === 'promos' ? 'active-tab' : '' ?>">
                    <i class="fa-solid fa-tag w-6"></i> Promos
                </a>
                <a href="?tab=branches" class="flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 hover:text-white transition-all <?= $activeTab === 'branches' ? 'active-tab' : '' ?>">
                    <i class="fa-solid fa-map-location-dot w-6"></i> Branches
                </a>
            </nav>
            <div class="p-4 border-t border-slate-800">
                <a href="index.php" target="_blank" class="flex items-center px-4 py-2 text-sm hover:text-white"><i class="fa-solid fa-eye mr-2"></i> View Site</a>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="flex-1 ml-64 min-h-screen">
            <header class="bg-white border-b h-16 flex items-center justify-between px-8 sticky top-0 z-40">
                <h2 class="text-lg font-bold text-slate-800"><?= ucfirst($activeTab) ?> Management</h2>
            </header>

            <div class="p-8 max-w-6xl mx-auto w-full">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center <?= $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
                        <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> mr-3"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($activeTab === 'settings'): ?>
                    <!-- Settings Tab -->
                    <form action="?tab=settings" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_settings">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 space-y-6">
                                <div class="bg-white rounded-2xl p-6 shadow-sm border">
                                    <h3 class="font-bold mb-4">Hero Section</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hero Image</label>
                                            <div class="flex items-center space-x-4">
                                                <img src="assets/images/<?= htmlspecialchars($settingsData['hero_image'] ?? 'hero.png') ?>" class="w-20 h-12 object-cover rounded-lg border">
                                                <input type="file" name="hero_image" class="text-xs">
                                            </div>
                                        </div>
                                        <input type="text" name="hero_title" value="<?= htmlspecialchars($settingsData['hero_title'] ?? '') ?>" placeholder="Title" class="w-full rounded-xl border p-3">
                                        <textarea name="hero_subtitle" rows="2" class="w-full rounded-xl border p-3"><?= htmlspecialchars($settingsData['hero_subtitle'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="bg-white rounded-2xl p-6 shadow-sm border">
                                    <h3 class="font-bold mb-4">About Section</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Story Image</label>
                                            <div class="flex items-center space-x-4">
                                                <img src="assets/images/<?= htmlspecialchars($settingsData['about_image'] ?? 'about.png') ?>" class="w-20 h-12 object-cover rounded-lg border">
                                                <input type="file" name="about_image" class="text-xs">
                                            </div>
                                        </div>
                                        <input type="text" name="about_title" value="<?= htmlspecialchars($settingsData['about_title'] ?? '') ?>" placeholder="Story Title" class="w-full rounded-xl border p-3">
                                        <textarea name="about_text" rows="4" class="w-full rounded-xl border p-3"><?= htmlspecialchars($settingsData['about_text'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div class="bg-white rounded-2xl p-6 shadow-sm border">
                                    <h3 class="font-bold mb-4">Social Media</h3>
                                    <div class="space-y-3">
                                        <?php foreach ($socialLinks as $link): ?>
                                            <div class="flex items-center justify-between bg-slate-50 p-2 rounded-lg border text-xs">
                                                <span class="font-bold truncate max-w-[120px]"><?= $link['platform'] ?>: <?= $link['url'] ?></span>
                                                <form action="?tab=settings" method="POST">
                                                    <input type="hidden" name="action" value="delete_social">
                                                    <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                                    <button class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="pt-4 border-t">
                                            <form action="?tab=settings" method="POST" class="space-y-2">
                                                <input type="hidden" name="action" value="add_social">
                                                <select name="platform" class="w-full text-xs rounded border p-2"><option>Facebook</option><option>Instagram</option><option>Twitter</option><option>TikTok</option></select>
                                                <input type="url" name="url" placeholder="URL" required class="w-full text-xs rounded border p-2">
                                                <button type="submit" class="w-full bg-slate-900 text-white text-xs font-bold py-2 rounded">Add Link</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end"><button type="submit" class="bg-blue-600 text-white px-10 py-3 rounded-2xl font-bold shadow-lg">Save Changes</button></div>
                    </form>

                <?php elseif ($activeTab === 'schedule'): ?>
                    <!-- Schedule Tab -->
                    <form action="?tab=schedule" method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_schedule">
                        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 border-b"><tr><th class="px-8 py-5 text-xs font-bold uppercase">Day</th><th class="px-8 py-5 text-xs font-bold uppercase">Status</th><th class="px-8 py-5 text-xs font-bold uppercase">Hours</th></tr></thead>
                                <tbody class="divide-y">
                                    <?php foreach ($schedule as $row): ?>
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-8 py-4 font-bold text-slate-700"><?= $days[$row['day_of_week']] ?></td>
                                            <td class="px-8 py-4">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" name="is_open_<?= $row['day_of_week'] ?>" value="1" <?= $row['is_open'] ? 'checked' : '' ?> class="sr-only peer">
                                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                                </label>
                                            </td>
                                            <td class="px-8 py-4 flex items-center space-x-2">
                                                <input type="time" name="open_time_<?= $row['day_of_week'] ?>" value="<?= $row['open_time'] ?>" class="rounded-lg border p-2 text-sm">
                                                <span>to</span>
                                                <input type="time" name="close_time_<?= $row['day_of_week'] ?>" value="<?= $row['close_time'] ?>" class="rounded-lg border p-2 text-sm">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end"><button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold">Update Schedule</button></div>
                    </form>

                <?php elseif ($activeTab === 'promos'): ?>
                    <!-- Promos Tab -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-1">
                            <div class="bg-white p-6 rounded-2xl shadow-sm border sticky top-24">
                                <h3 class="font-bold mb-4" id="promo-form-title">Add New Promo</h3>
                                <form action="?tab=promos" method="POST" enctype="multipart/form-data" class="space-y-4" id="promo-form">
                                    <input type="hidden" name="action" value="add_promo" id="promo-action">
                                    <input type="hidden" name="id" value="" id="promo-id">
                                    <input type="text" name="title" id="p-title" placeholder="Title" required class="w-full rounded-xl border p-2">
                                    <textarea name="description" id="p-desc" placeholder="Description" required rows="3" class="w-full rounded-xl border p-2"></textarea>
                                    <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Upload Image</label><input type="file" name="promo_image" class="text-xs"></div>
                                    <input type="datetime-local" name="valid_until" id="p-date" required class="w-full rounded-xl border p-2">
                                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-xl font-bold" id="promo-submit">Add Promo</button>
                                    <button type="button" onclick="resetPromoForm()" class="w-full bg-slate-100 px-6 py-2 rounded-xl font-bold hidden" id="promo-cancel">Cancel</button>
                                </form>
                            </div>
                        </div>
                        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($promos as $promo): ?>
                                <div class="bg-white rounded-2xl shadow-sm border overflow-hidden flex flex-col group">
                                    <div class="h-40 relative">
                                        <img src="assets/images/<?= $promo['image_url'] ?>" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                            <button onclick='editPromo(<?= json_encode($promo) ?>)' class="w-8 h-8 rounded-full bg-white text-blue-600 flex items-center justify-center"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <form action="?tab=promos" method="POST"><input type="hidden" name="action" value="delete_promo"><input type="hidden" name="id" value="<?= $promo['id'] ?>"><button class="w-8 h-8 rounded-full bg-white text-red-600 flex items-center justify-center"><i class="fa-solid fa-trash"></i></button></form>
                                        </div>
                                    </div>
                                    <div class="p-4"><h4 class="font-bold"><?= htmlspecialchars($promo['title']) ?></h4><p class="text-[10px] text-slate-400 mt-2 font-bold">Ends <?= date('M d, Y', strtotime($promo['valid_until'])) ?></p></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($activeTab === 'branches'): ?>
                    <!-- Branches Tab -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-1">
                            <div class="bg-white p-6 rounded-2xl shadow-sm border sticky top-24">
                                <h3 class="font-bold mb-4">Add New Branch</h3>
                                <form action="?tab=branches" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="add_branch">
                                    <input type="text" name="name" required placeholder="Branch Name" class="w-full rounded-xl border p-2">
                                    <textarea name="address" required rows="2" placeholder="Address (Street, City)" class="w-full rounded-xl border p-2"></textarea>
                                    <textarea name="google_maps_embed" rows="3" class="w-full rounded-xl border p-2" placeholder="Optional Embed HTML"></textarea>
                                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-xl font-bold">Add Branch</button>
                                </form>
                            </div>
                        </div>
                        <div class="lg:col-span-2 space-y-4">
                            <?php foreach ($branches as $branch): ?>
                                <div class="bg-white p-6 rounded-2xl shadow-sm border flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl"><i class="fa-solid fa-location-dot"></i></div>
                                        <div><h4 class="font-bold"><?= htmlspecialchars($branch['name']) ?></h4><p class="text-sm text-slate-500"><?= htmlspecialchars($branch['address']) ?></p></div>
                                    </div>
                                    <form action="?tab=branches" method="POST"><input type="hidden" name="action" value="delete_branch"><input type="hidden" name="id" value="<?= $branch['id'] ?>"><button class="w-10 h-10 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center"><i class="fa-solid fa-trash-can"></i></button></form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function editPromo(promo) {
            document.getElementById('promo-form-title').innerText = 'Edit Promo';
            document.getElementById('promo-action').value = 'edit_promo';
            document.getElementById('promo-id').value = promo.id;
            document.getElementById('p-title').value = promo.title;
            document.getElementById('p-desc').value = promo.description;
            let date = new Date(promo.valid_until);
            document.getElementById('p-date').value = date.toISOString().slice(0, 16);
            document.getElementById('promo-submit').innerText = 'Save Changes';
            document.getElementById('promo-cancel').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function resetPromoForm() {
            document.getElementById('promo-form-title').innerText = 'Add New Promo';
            document.getElementById('promo-action').value = 'add_promo';
            document.getElementById('promo-id').value = '';
            document.getElementById('promo-form').reset();
            document.getElementById('promo-submit').innerText = 'Add Promo';
            document.getElementById('promo-cancel').classList.add('hidden');
        }
    </script>
</body>
</html>
