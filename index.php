<?php
// =========================
// Fungsi baca JSON
// =========================
function parse_json_file($file) {
    if (!file_exists($file)) return null;
    return json_decode(file_get_contents($file), true);
}

// =========================
// Fungsi ambil username dari JSON Instagram terbaru
// =========================
function extract_usernames($data) {
    $list = [];
    if (!$data) return $list;

    // Struktur baru Instagram
    $possible_keys = [
        'relationships_followers',
        'relationships_following',
        'followers',
        'following',
        'connections'
    ];

    foreach ($possible_keys as $key) {
        if (isset($data[$key])) {
            $data = $data[$key];
            break;
        }
    }

    // Jika data nested
    if (isset($data['data'])) {
        $data = $data['data'];
    }

    foreach ($data as $item) {
        $username = '';

        // Format baru Instagram
        if (isset($item['string_list_data'][0]['value'])) {
            $username = $item['string_list_data'][0]['value'];
        }
        // Format baru IG 2025+
        elseif (isset($item['username'])) {
            $username = $item['username'];
        }
        // Format lama
        elseif (isset($item['title'])) {
            $username = $item['title'];
        }
        elseif (isset($item['value'])) {
            $username = $item['value'];
        }

        // Normalisasi username
        $username = strtolower(trim($username));
        $username = preg_replace('/[^a-z0-9._]/', '', $username);

        if ($username !== '') {
            $list[] = $username;
        }
    }

    return array_unique($list);
}

$hasil = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_FILES['followers']) || !isset($_FILES['following'])) {
        $error = "⚠️ Upload kedua file JSON!";
    } else {

        $followers_data = parse_json_file($_FILES['followers']['tmp_name']);
        $following_data = parse_json_file($_FILES['following']['tmp_name']);

        if (!$followers_data || !$following_data) {
            $error = "❌ File JSON tidak valid atau rusak!";
        } else {

            $followers = extract_usernames($followers_data);
            $following = extract_usernames($following_data);

            if (empty($followers) || empty($following)) {
                $error = "❌ Struktur JSON tidak sesuai atau kosong. Pastikan file dari Data Download Instagram!";
            } else {
                // Unfollowers = kamu follow tapi dia tidak follow balik
                $hasil = array_values(array_diff($following, $followers));
                sort($hasil);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Instagram Unfollowers Checker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f6f7fb; }
.header {
    background: linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045);
    color:white;
    padding:20px;
    border-radius:12px 12px 0 0;
}
.list-group-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.username-link {
    color:#0d6efd;
    text-decoration:none;
}
.username-link:hover { text-decoration:underline; }

@media print {
    .no-print { display:none; }
}
</style>
</head>

<body>
<div class="container my-5">
<div class="card shadow-lg">

<div class="header text-center">
<h3>🔍 Cek Unfollowers Instagram (Update 2026)</h3>
<p>Cek siapa yang tidak follow kamu balik</p>
</div>

<div class="card-body">

<form method="post" enctype="multipart/form-data" class="no-print">
<div class="mb-3">
<label class="form-label">Upload followers JSON</label>
<input type="file" name="followers" class="form-control" accept=".json" required>
</div>

<div class="mb-3">
<label class="form-label">Upload following JSON</label>
<input type="file" name="following" class="form-control" accept=".json" required>
</div>

<button class="btn btn-primary w-100">🔎 Cek Sekarang</button>
</form>

<?php if ($error): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($hasil): ?>
<div class="mt-4">
<div class="d-flex justify-content-between align-items-center">
<h5>❌ Tidak follow balik (<?= count($hasil) ?> akun)</h5>
<button onclick="window.print()" class="btn btn-danger btn-sm no-print">🖨 Print</button>
</div>

<ul class="list-group mt-3">
<?php foreach ($hasil as $u): ?>
<li class="list-group-item">
<span>@<?= htmlspecialchars($u) ?></span>
<a href="https://instagram.com/<?= urlencode($u) ?>" target="_blank" class="username-link">Lihat</a>
</li>
<?php endforeach; ?>
</ul>
</div>

<?php elseif ($_SERVER['REQUEST_METHOD']=='POST' && !$error): ?>
<div class="alert alert-success mt-3">🎉 Semua akun follow balik!</div>
<?php endif; ?>

</div>
</div>
</div>

</body>
</html>
