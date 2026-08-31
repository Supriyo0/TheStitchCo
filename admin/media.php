<?php
/**
 * Admin Media Storage & ImgBB Cloud Uploader
 * The Stitch Co.
 */

$adminTitle = 'Media Storage & ImgBB Gallery';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Handle Direct Upload to ImgBB / Local Storage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    if (!empty($_FILES['media_file']['name'])) {
        $useImgbb = isset($_POST['upload_to_imgbb']);
        if ($useImgbb) {
            $res = upload_to_imgbb($_FILES['media_file']);
        } else {
            $folder = $_POST['target_folder'] ?? 'products';
            $res = handle_image_upload($_FILES['media_file'], $folder, 'storage');
        }

        if ($res['success']) {
            $finalUrl = $res['url'] ?? $res['relative_url'];
            $msg = 'Image uploaded successfully! Path/URL: ' . $finalUrl;
        } else {
            $err = $res['message'] ?? 'Upload failed.';
        }
    } else {
        $err = 'Please select a file to upload.';
    }
}

// Handle File Delete
if (isset($_GET['del_file'])) {
    $filePath = trim($_GET['del_file']);
    $realFullPath = realpath(__DIR__ . '/../' . $filePath);
    $allowedBase = realpath(__DIR__ . '/../');
    if ($realFullPath && strpos($realFullPath, $allowedBase) === 0 && file_exists($realFullPath) && is_file($realFullPath)) {
        @unlink($realFullPath);
        $msg = 'File deleted successfully!';
    } else {
        $err = 'Unable to delete file (file does not exist or invalid path).';
    }
}

// Scan storage directories for media files
$mediaFiles = [];
$dirsToScan = [
    'uploads/products' => '../uploads/products',
    'uploads/banners' => '../uploads/banners',
    'uploads/proofs' => '../uploads/proofs',
    'uploads/media' => '../uploads/media',
    'assets/images/products' => '../assets/images/products',
    'assets/images/banners' => '../assets/images/banners',
    'assets/images' => '../assets/images'
];

foreach ($dirsToScan as $label => $dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..' && !is_dir($dir . '/' . $f)) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif'])) {
                    $mediaFiles[] = [
                        'name' => $f,
                        'folder' => $label,
                        'path' => str_replace('../', '', $dir . '/' . $f),
                        'size' => round(filesize($dir . '/' . $f) / 1024, 1) . ' KB',
                        'time' => filemtime($dir . '/' . $f)
                    ];
                }
            }
        }
    }
}

// Sort by latest
usort($mediaFiles, function($a, $b) {
    return $b['time'] - $a['time'];
});
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">⚠️ <?= e($err) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Media Storage & ImgBB Cloud Library</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Pick images from local storage or upload directly to ImgBB CDN for lightning-fast delivery.</span>
        </div>
    </div>

    <!-- Upload Box -->
    <div style="padding: 1.8rem; background: #FAFBFD; border-bottom: 1px solid var(--admin-border);">
        <form action="media.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 1.2rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Select Image File (JPG, PNG, WEBP, SVG)</label>
                <input type="file" name="media_file" required accept="image/*" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Target Storage Folder</label>
                <select name="target_folder" style="padding: 0.65rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff; font-weight: 700;">
                    <option value="products">uploads/products</option>
                    <option value="banners">uploads/banners</option>
                    <option value="proofs">uploads/proofs</option>
                    <option value="media">uploads/media</option>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; padding-bottom: 0.8rem;">
                <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 800; font-size: 0.82rem; color: #2563EB; cursor: pointer;">
                    <input type="checkbox" name="upload_to_imgbb" value="1" checked>
                    <span>☁️ Upload to ImgBB CDN</span>
                </label>
            </div>
            <div>
                <button type="submit" name="upload_media" style="padding: 0.75rem 1.8rem; background: #2563EB; color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    UPLOAD IMAGE ⬆
                </button>
            </div>
        </form>
    </div>

    <!-- Media Grid Gallery -->
    <div style="padding: 1.8rem;">
        <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800; margin-bottom: 1.2rem;">All Stored Media Assets (<?= count($mediaFiles) ?>)</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1.2rem;">
            <?php foreach ($mediaFiles as $m): ?>
                <div style="background: #FFFFFF; border: 1.5px solid var(--admin-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s;">
                    <div style="height: 140px; background: #F3F4F6; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 6px;">
                        <img src="../<?= e($m['path']) ?>" alt="" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>
                    <div style="padding: 0.8rem; display: flex; flex-direction: column; gap: 0.5rem; flex: 1; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 800; word-break: break-all; color: var(--admin-text-main); line-height: 1.3;" title="<?= e($m['name']) ?>">
                                <?= e(mb_strimwidth($m['name'], 0, 22, '...')) ?>
                            </div>
                            <div style="font-size: 0.7rem; color: var(--admin-text-muted); margin-top: 0.2rem;">
                                <?= e($m['folder']) ?> • <?= $m['size'] ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.4rem;">
                            <button type="button" onclick="copyToClipboard('<?= e($m['path']) ?>')" style="flex: 1; padding: 0.4rem; background: #EEF2FF; color: #1E3A8A; border: 1px solid #C7D2FE; border-radius: 4px; font-size: 0.72rem; font-weight: 800; cursor: pointer; text-align: center;">
                                📋 Copy
                            </button>
                            <a href="media.php?del_file=<?= urlencode($m['path']) ?>" onclick="return confirm('Delete file <?= e($m['name']) ?> permanently?')" style="padding: 0.4rem 0.6rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 4px; font-size: 0.72rem; font-weight: 800; text-decoration: none; text-align: center;" title="Delete file">
                                🗑️
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Image path copied to clipboard:\n' + text);
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
