<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "majestic_vote_db");
if (!$conn) die("Database Connection Failed");

/* =========================
   Upload Directory Setup
========================= */
$upload_dir = __DIR__ . '/uploads/participants/';
$upload_url = 'uploads/participants/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    // Block script execution inside uploads folder
    file_put_contents($upload_dir . '.htaccess',
        "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp\nRemoveHandler .php\nphp_flag engine off\n"
    );
}

/* =========================
   Candidate Images (hardcoded originals)
========================= */
$candidate_images = [
    "Steve"      => "https://upload.wikimedia.org/wikipedia/en/8/8b/ST3_Steve_Harrington_portrait.jpg",
    "Nancy"      => "https://upload.wikimedia.org/wikipedia/pt/3/3c/Nancy_Wheeler_%28Natalia_Dyer%29.jpg",
    "Jonathan"   => "https://upload.wikimedia.org/wikipedia/pt/thumb/a/a9/Jonathan_Byers_%28Charlie_Heaton%29.jpg/250px-Jonathan_Byers_%28Charlie_Heaton%29.jpg",
    "Joyce"      => "https://cdn.polyspeak.ai/speakmaster/poly-sdispatcher/superresolution/images/20250404/99396f09-85c9-43d2-a414-bf9a13f9f20e.webp",
    "Jim Hopper" => "https://upload.wikimedia.org/wikipedia/en/0/08/JimHopperST.png",
    "Bob"        => "https://static.tvmaze.com/uploads/images/medium_portrait/132/332043.jpg"
];

/* =========================
   Ensure Participants Table Exists
========================= */
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS participants (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        full_name     VARCHAR(120)  NOT NULL,
        email         VARCHAR(180)  NOT NULL UNIQUE,
        phone         VARCHAR(20)   NOT NULL,
        dob           DATE          NOT NULL,
        gender        ENUM('Male','Female','Other') NOT NULL,
        address       TEXT          NOT NULL,
        id_number     VARCHAR(60)   NOT NULL,
        profile_image VARCHAR(255)  DEFAULT NULL,
        vote_count    INT           DEFAULT 0,
        registered_at DATETIME      DEFAULT CURRENT_TIMESTAMP
    )
");
// Upgrade old schema silently
@mysqli_query($conn, "ALTER TABLE participants ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
@mysqli_query($conn, "ALTER TABLE participants ADD COLUMN IF NOT EXISTS vote_count INT DEFAULT 0");

/* =========================
   Secure Image Upload Helper
========================= */
function handleImageUpload(array $file, string $upload_dir): array {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return ['filename' => null];
    if ($file['error'] !== UPLOAD_ERR_OK)       return ['error' => 'Upload error. Please try again.'];
    if ($file['size'] > 3 * 1024 * 1024)        return ['error' => 'Image must be under 3 MB.'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) return ['error' => 'Only JPG, PNG, GIF, or WEBP images are allowed.'];

    $filename = 'participant_' . uniqid('', true) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename))
        return ['error' => 'Could not save image. Check folder permissions.'];

    return ['filename' => $filename];
}

/* =========================
   Build Unified Candidates List
========================= */
$candidates = [];

// Original candidates from vote table
$r = mysqli_query($conn, "SELECT * FROM vote ORDER BY count DESC");
while ($row = mysqli_fetch_assoc($r)) {
    $row['img']    = $candidate_images[$row['name']] ?? 'https://via.placeholder.com/300x400';
    $row['source'] = 'original';
    $row['uid']    = 'o_' . $row['name'];
    $candidates[]  = $row;
}

// Registered participants
$r2 = mysqli_query($conn, "SELECT * FROM participants ORDER BY vote_count DESC");
while ($row = mysqli_fetch_assoc($r2)) {
    $img = $row['profile_image']
        ? $upload_url . $row['profile_image']
        : 'https://ui-avatars.com/api/?name=' . urlencode($row['full_name'])
          . '&size=400&background=1a2236&color=c9a84c&bold=true&length=2';
    $candidates[] = [
        'uid'    => 'p_' . $row['id'],
        'name'   => $row['full_name'],
        'party'  => 'Registered Participant',
        'count'  => $row['vote_count'],
        'img'    => $img,
        'source' => 'participant',
        'pid'    => $row['id'],
    ];
}

// Sort unified list by votes descending
usort($candidates, fn($a, $b) => $b['count'] - $a['count']);

/* =========================
   Handle Voting
========================= */
if (isset($_POST['vote']) && !empty($_POST['candidate'])) {
    $raw = $_POST['candidate'];

    if (str_starts_with($raw, 'p_')) {
        $pid = (int) substr($raw, 2);
        mysqli_query($conn, "UPDATE participants SET vote_count = vote_count + 1 WHERE id = $pid");
        $nr           = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM participants WHERE id=$pid"));
        $display_name = $nr['full_name'] ?? 'Participant';
    } else {
        $cname = mysqli_real_escape_string($conn, substr($raw, 2)); // strip 'o_'
        mysqli_query($conn, "UPDATE vote SET count = count + 1 WHERE name = '$cname'");
        $display_name = $cname;
    }

    $_SESSION['voted'] = true;
    header("Location: " . $_SERVER['PHP_SELF'] . "?done=1&candidate=" . urlencode($display_name));
    exit();
}

/* =========================
   Handle Participant Registration
========================= */
$reg_success = false;
$reg_error   = '';
$new_part_id = null;

if (isset($_POST['register_participant'])) {
    $full_name = trim(mysqli_real_escape_string($conn, $_POST['full_name'] ?? ''));
    $email     = trim(mysqli_real_escape_string($conn, $_POST['email']     ?? ''));
    $phone     = trim(mysqli_real_escape_string($conn, $_POST['phone']     ?? ''));
    $dob       = trim(mysqli_real_escape_string($conn, $_POST['dob']       ?? ''));
    $gender    = trim(mysqli_real_escape_string($conn, $_POST['gender']    ?? ''));
    $address   = trim(mysqli_real_escape_string($conn, $_POST['address']   ?? ''));
    $id_number = trim(mysqli_real_escape_string($conn, $_POST['id_number'] ?? ''));

    if (!$full_name || !$email || !$phone || !$dob || !$gender || !$address || !$id_number) {
        $reg_error = 'All fields are required. Please fill in every section.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $reg_error = 'Please enter a valid email address.';
    } else {
        // Handle image upload
        $img_filename = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $upload_result = handleImageUpload($_FILES['profile_image'], $upload_dir);
            if (isset($upload_result['error'])) {
                $reg_error = $upload_result['error'];
            } else {
                $img_filename = $upload_result['filename'];
            }
        }

        if (!$reg_error) {
            $check = mysqli_query($conn, "SELECT id FROM participants WHERE email='$email'");
            if (mysqli_num_rows($check) > 0) {
                $reg_error = 'This email is already registered.';
                if ($img_filename) @unlink($upload_dir . $img_filename);
            } else {
                $img_sql = $img_filename ? "'$img_filename'" : "NULL";
                $ins = mysqli_query($conn,
                    "INSERT INTO participants (full_name, email, phone, dob, gender, address, id_number, profile_image)
                     VALUES ('$full_name','$email','$phone','$dob','$gender','$address','$id_number',$img_sql)"
                );
                if ($ins) {
                    $reg_success = true;
                    $new_part_id = mysqli_insert_id($conn);
                    $_SESSION['participant_name'] = $full_name;
                } else {
                    $reg_error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

/* =========================
   Stats
========================= */
$total_votes = array_sum(array_column($candidates, 'count'));
$leader      = $candidates[0] ?? ['name' => '—'];

$orig_count  = count(array_filter($candidates, fn($c) => $c['source'] === 'original'));
$part_count  = count(array_filter($candidates, fn($c) => $c['source'] === 'participant'));

$voted_for   = $_GET['candidate'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MajesticVote — Official Election</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
    --gold:#c9a84c;--gold-light:#f0d080;--gold-dim:rgba(201,168,76,.15);
    --dark:#0a0e1a;--dark2:#111827;--card:#161d2e;
    --border:rgba(201,168,76,.2);--border-hi:rgba(201,168,76,.5);
    --text:#eef2ff;--muted:#94a3b8;--success:#22c55e;--error:#ef4444;
}
body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);min-height:100vh;overflow-x:hidden;
    background-image:radial-gradient(ellipse at top left,rgba(201,168,76,.12),transparent 35%),
    radial-gradient(ellipse at bottom right,rgba(124,58,237,.14),transparent 40%)}

/* ── HEADER ── */
header{text-align:center;padding:70px 20px 40px;border-bottom:1px solid var(--border);position:relative;overflow:hidden}
header::before{content:'';position:absolute;inset:0;pointer-events:none;
    background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23c9a84c' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.badge{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:40px;
    background:rgba(201,168,76,.08);border:1px solid var(--border);color:var(--gold);
    font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-bottom:20px}
.badge::before{content:'●';color:#4ade80;font-size:8px;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
h1{font-family:'Playfair Display',serif;font-size:clamp(2.4rem,5vw,4rem);line-height:1.2;margin-bottom:14px;
    background:linear-gradient(135deg,#fff,var(--gold-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
header p{color:var(--muted);font-size:15px}

/* ── MAIN ── */
main{max-width:1240px;margin:auto;padding:50px 20px 80px}

/* ── STATS ── */
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:22px;margin-bottom:50px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:28px;text-align:center;transition:.3s}
.stat-card:hover{transform:translateY(-5px);border-color:var(--gold)}
.stat-icon{width:65px;height:65px;margin:0 auto 16px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    background:rgba(201,168,76,.1);color:var(--gold);font-size:22px}
.stat-num{font-size:1.8rem;font-weight:700;margin-bottom:6px}
.stat-label{color:var(--muted);font-size:13px}

/* ── FILTER TABS ── */
.section-tabs{display:flex;gap:12px;margin-bottom:30px;flex-wrap:wrap}
.tab-btn{padding:10px 22px;border-radius:30px;border:1px solid var(--border);background:rgba(255,255,255,.03);
    color:var(--muted);font-size:13px;letter-spacing:1px;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif}
.tab-btn.active,.tab-btn:hover{background:rgba(201,168,76,.12);border-color:var(--gold);color:var(--gold)}
.count-pill{display:inline-block;padding:2px 8px;border-radius:20px;background:var(--gold-dim);
    color:var(--gold);font-size:11px;margin-left:6px;font-weight:600}

/* ── TYPE BADGES ── */
.type-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;
    font-size:10px;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;border:1px solid}
.type-badge.original{color:#a78bfa;background:rgba(124,58,237,.12);border-color:rgba(124,58,237,.35)}
.type-badge.participant{color:var(--gold);background:rgba(201,168,76,.08);border-color:var(--border)}

/* ── CANDIDATE GRID ── */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:24px}
.instruction{text-align:center;color:var(--muted);margin-bottom:30px;text-transform:uppercase;letter-spacing:2px;font-size:12px}

/* ── CARD ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden;transition:.3s;position:relative}
.card:hover{transform:translateY(-6px);border-color:var(--gold);box-shadow:0 20px 50px rgba(0,0,0,.3)}
.card input{display:none}
.card label{display:flex;flex-direction:column;height:100%;cursor:pointer}
.card:has(input:checked){border-color:var(--gold);box-shadow:0 0 0 1px var(--gold),0 20px 50px rgba(201,168,76,.2)}
.card-img{width:100%;aspect-ratio:4/3;object-fit:cover;object-position:top;filter:grayscale(15%);transition:.3s}
.card:hover .card-img,.card:has(input:checked) .card-img{filter:grayscale(0%)}
.card-body{padding:22px;flex:1}
.party-tag{display:inline-block;color:var(--gold);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px}
.card-body h2{font-family:'Playfair Display',serif;font-size:1.55rem;margin-bottom:8px}
.card-body p{color:var(--muted);line-height:1.7;font-size:13px}
.vote-count{margin-top:14px;font-weight:600;font-size:14px}
.select-btn{margin-top:18px;padding:13px;border-radius:10px;border:1px solid var(--border);text-align:center;
    background:rgba(201,168,76,.08);color:var(--muted);text-transform:uppercase;letter-spacing:1px;font-size:13px;transition:.3s}
.card:has(input:checked) .select-btn{background:var(--gold);color:var(--dark);border-color:var(--gold);font-weight:700}

/* ── SUBMIT ── */
.submit-wrap{text-align:center;margin-top:45px}
button{border:none;cursor:pointer}
button[type="submit"]{padding:16px 55px;border-radius:50px;background:linear-gradient(135deg,var(--gold),#e8b84b);
    color:var(--dark);font-weight:700;font-size:14px;letter-spacing:2px;text-transform:uppercase;transition:.3s;
    box-shadow:0 10px 30px rgba(201,168,76,.25)}
button[type="submit"]:hover{transform:translateY(-3px)}
.disclaimer{margin-top:14px;color:var(--muted);font-size:13px}

/* ── THANK YOU ── */
.thankyou{display:flex;flex-direction:column;align-items:center;text-align:center;gap:20px;padding:80px 20px}
.checkmark{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);
    display:flex;align-items:center;justify-content:center;font-size:38px;box-shadow:0 0 0 14px rgba(34,197,94,.12)}
.thankyou h2{font-family:'Playfair Display',serif;font-size:2.7rem}
.thankyou p{color:var(--muted);max-width:500px;line-height:1.7}
.voted-name{padding:12px 24px;border-radius:30px;border:1px solid var(--border);background:rgba(201,168,76,.08);color:var(--gold)}

/* ── DIVIDER ── */
.section-divider{display:flex;align-items:center;gap:20px;margin:70px 0 50px}
.section-divider::before,.section-divider::after{content:'';flex:1;height:1px;
    background:linear-gradient(90deg,transparent,var(--border),transparent)}
.divider-label{display:flex;align-items:center;gap:10px;padding:10px 24px;border-radius:40px;
    border:1px solid var(--border);background:rgba(201,168,76,.06);color:var(--gold);
    font-size:11px;letter-spacing:2.5px;text-transform:uppercase;white-space:nowrap}

/* ── PORTAL WRAPPER ── */
.portal-wrapper{background:var(--card);border:1px solid var(--border);border-radius:28px;overflow:hidden;position:relative}
.portal-wrapper::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,transparent,var(--gold),var(--gold-light),var(--gold),transparent)}
.portal-hero{padding:50px 40px 40px;display:flex;align-items:center;justify-content:space-between;gap:30px;
    border-bottom:1px solid var(--border);flex-wrap:wrap;
    background:radial-gradient(ellipse at top right,rgba(201,168,76,.09),transparent 60%),
               radial-gradient(ellipse at bottom left,rgba(124,58,237,.1),transparent 60%)}
.portal-hero-text h2{font-family:'Playfair Display',serif;font-size:clamp(1.8rem,3vw,2.6rem);margin-bottom:12px;
    background:linear-gradient(135deg,#fff 40%,var(--gold-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.portal-hero-text p{color:var(--muted);line-height:1.7;max-width:480px;font-size:15px}
.portal-features{display:flex;gap:14px;flex-wrap:wrap;margin-top:22px}
.feat-pill{display:flex;align-items:center;gap:7px;padding:7px 14px;border-radius:30px;
    background:rgba(201,168,76,.08);border:1px solid var(--border);color:var(--gold);font-size:12px;letter-spacing:1px}
.portal-badge-wrap{text-align:center;flex-shrink:0}
.portal-icon-ring{width:110px;height:110px;border-radius:50%;background:rgba(201,168,76,.08);
    border:1px solid var(--border);display:flex;align-items:center;justify-content:center;
    font-size:40px;color:var(--gold);margin:0 auto 12px;position:relative}
.portal-icon-ring::before{content:'';position:absolute;inset:-6px;border-radius:50%;
    border:1px dashed var(--border-hi);animation:spin 20s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.portal-badge-wrap span{font-size:12px;letter-spacing:2px;text-transform:uppercase;color:var(--muted)}

/* ── REGISTRATION FORM ── */
.reg-form-wrap{padding:40px}
.reg-form-wrap h3{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--gold);
    letter-spacing:1px;margin-bottom:30px;display:flex;align-items:center;gap:10px}
.reg-form-wrap h3::after{content:'';flex:1;height:1px;background:var(--border)}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
.form-group{display:flex;flex-direction:column;gap:8px}
.form-group.full-width{grid-column:1/-1}
.form-group>label{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);font-weight:600}
.form-group>label i{margin-right:6px;opacity:.7}
.form-group input,.form-group select,.form-group textarea{
    background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:12px;
    padding:14px 18px;color:var(--text);font-family:'Inter',sans-serif;font-size:14px;
    transition:.25s;outline:none;width:100%;appearance:none;-webkit-appearance:none}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{
    border-color:var(--gold);background:rgba(201,168,76,.06);box-shadow:0 0 0 3px rgba(201,168,76,.1)}
.form-group input::placeholder,.form-group textarea::placeholder{color:rgba(148,163,184,.5)}
.form-group textarea{resize:vertical;min-height:100px}

/* ── IMAGE UPLOAD ZONE ── */
.img-upload-zone{border:2px dashed var(--border);border-radius:16px;padding:34px 20px;text-align:center;
    cursor:pointer;transition:.3s;position:relative;background:rgba(255,255,255,.02);overflow:hidden}
.img-upload-zone:hover,.img-upload-zone.dragover{border-color:var(--gold);background:rgba(201,168,76,.06)}
.img-upload-zone input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-icon{font-size:34px;color:var(--gold);opacity:.65;margin-bottom:12px}
.upload-hint{color:var(--muted);font-size:13px;line-height:1.7}
.upload-hint strong{color:var(--text)}
.img-preview-wrap{display:none;margin-top:16px;align-items:center;gap:14px;
    padding:14px;border-radius:12px;border:1px solid var(--border);background:rgba(255,255,255,.03)}
.img-preview-wrap.visible{display:flex}
.img-preview{width:62px;height:62px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);flex-shrink:0}
.img-info{flex:1;text-align:left}
.img-info .fn{font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px}
.img-info .fs{font-size:12px;color:var(--muted)}
.btn-remove{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5;
    padding:7px 13px;border-radius:8px;font-size:12px;cursor:pointer;transition:.2s;flex-shrink:0}
.btn-remove:hover{background:rgba(239,68,68,.25)}

/* ── GENDER BTNS ── */
.gender-btns{display:flex;gap:10px;flex-wrap:wrap}
.gender-btn{flex:1;padding:13px 10px;border-radius:12px;border:1px solid var(--border);
    background:rgba(255,255,255,.04);color:var(--muted);font-size:13px;cursor:pointer;
    transition:.2s;text-align:center;min-width:90px;display:flex;align-items:center;justify-content:center;gap:6px;font-family:'Inter',sans-serif}
.gender-btn.active,.gender-btn:hover{border-color:var(--gold);background:rgba(201,168,76,.1);color:var(--gold)}

/* ── TERMS ── */
.terms-row{display:flex;align-items:flex-start;gap:12px;margin-top:8px}
.terms-row input[type="checkbox"]{width:18px;height:18px;flex-shrink:0;accent-color:var(--gold);cursor:pointer;margin-top:2px}
.terms-row>label{font-size:13px;color:var(--muted);line-height:1.6;cursor:pointer;text-transform:none;letter-spacing:0;font-weight:400}
.terms-row>label a{color:var(--gold);text-decoration:none}

/* ── SUBMIT ROW ── */
.reg-submit-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
    gap:16px;margin-top:32px;padding-top:28px;border-top:1px solid var(--border)}
.security-note{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:13px}
.security-note i{color:var(--gold)}
.btn-register{padding:16px 50px;border-radius:50px;background:linear-gradient(135deg,var(--gold),#e8b84b);
    color:var(--dark);font-weight:700;font-size:14px;letter-spacing:2px;text-transform:uppercase;transition:.3s;
    box-shadow:0 10px 30px rgba(201,168,76,.3);border:none;cursor:pointer;display:inline-flex;align-items:center;gap:10px;font-family:'Inter',sans-serif}
.btn-register:hover{transform:translateY(-3px);box-shadow:0 16px 40px rgba(201,168,76,.4)}

/* ── ALERT ── */
.alert{padding:18px 22px;border-radius:14px;margin-bottom:28px;display:flex;align-items:flex-start;gap:14px;font-size:14px;line-height:1.6}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.alert-error i{color:var(--error);flex-shrink:0;margin-top:2px}

/* ── SUCCESS ── */
.reg-success{padding:60px 40px;text-align:center}
.success-avatar{width:100px;height:100px;border-radius:50%;object-fit:cover;
    border:3px solid var(--gold);margin:0 auto 0;display:block;
    box-shadow:0 0 0 8px rgba(201,168,76,.12);animation:popIn .5s cubic-bezier(.175,.885,.32,1.275)}
.success-ring{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#16a34a,#22c55e);
    display:flex;align-items:center;justify-content:center;font-size:42px;margin:16px auto 24px;
    box-shadow:0 0 0 16px rgba(34,197,94,.1),0 0 0 32px rgba(34,197,94,.05);
    animation:popIn .5s .1s both cubic-bezier(.175,.885,.32,1.275)}
@keyframes popIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.reg-success h2{font-family:'Playfair Display',serif;font-size:2.2rem;margin-bottom:14px;
    background:linear-gradient(135deg,#fff,var(--gold-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.reg-success p{color:var(--muted);line-height:1.7;max-width:460px;margin:0 auto 24px}
.participant-id-badge{display:inline-flex;align-items:center;gap:10px;padding:14px 28px;border-radius:40px;
    background:rgba(201,168,76,.1);border:1px solid var(--border);color:var(--gold);
    font-family:'Cormorant Garamond',serif;font-size:1.1rem;letter-spacing:1px}

/* ── FOOTER ── */
footer{text-align:center;padding:30px;border-top:1px solid var(--border);color:var(--muted);font-size:13px}

/* ── RESPONSIVE ── */
@media(max-width:768px){
    .grid,.stats{grid-template-columns:1fr}
    button[type="submit"],.btn-register{width:100%;justify-content:center}
    .portal-hero{padding:32px 24px}
    .reg-form-wrap{padding:28px 24px}
    .reg-submit-row{flex-direction:column}
    .portal-icon-ring{display:none}
    .form-grid{grid-template-columns:1fr}
    .section-tabs{gap:8px}
}
input[type="date"]::-webkit-calendar-picker-indicator{
    filter:invert(0.7) sepia(1) hue-rotate(10deg) saturate(2);cursor:pointer}
</style>
</head>
<body>
<!-- HOME BUTTON — paste at top of every experiment -->
<style>
.home-btn{
  position:fixed;top:16px;left:16px;z-index:9999;
  display:flex;align-items:center;gap:8px;
  padding:9px 18px;border-radius:30px;
  background:rgba(10,10,15,0.85);
  border:1px solid rgba(255,255,255,0.12);
  color:#fff;font-size:13px;font-weight:600;
  text-decoration:none;font-family:'DM Sans',sans-serif;
  backdrop-filter:blur(12px);
  box-shadow:0 4px 20px rgba(0,0,0,0.3);
  transition:all .2s;
}
.home-btn:hover{
  background:rgba(102,126,234,0.85);
  border-color:rgba(102,126,234,0.5);
  transform:translateY(-2px);
  box-shadow:0 6px 24px rgba(102,126,234,0.4);
}
.home-btn::before{content:'⌂';font-size:15px;}
</style>
<a href="index.php" class="home-btn">Home</a>
<header>
    <div class="badge">Live Voting · 2026 Election</div>
    <h1>Cast Your Official Vote</h1>
    <p>Secure Digital Ballot System · Live Election Portal</p>
</header>

<main>

<?php if (isset($_GET['done'])): ?>
<!-- ── THANK YOU ── -->
<div class="thankyou">
    <div class="checkmark">✓</div>
    <h2>Thank You!</h2>
    <p>Your vote has been securely recorded. Democracy grows stronger through participation.</p>
    <div class="voted-name">You voted for <strong><?= htmlspecialchars($voted_for) ?></strong></div>
</div>

<?php else: ?>

<!-- ── STATS ── -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-to-slot"></i></div>
        <div class="stat-num"><?= number_format($total_votes) ?></div>
        <div class="stat-label">Total Votes Cast</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-crown"></i></div>
        <div class="stat-num" style="font-size:1.3rem;line-height:1.3"><?= htmlspecialchars($leader['name']) ?></div>
        <div class="stat-label">Current Leader</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-list"></i></div>
        <div class="stat-num"><?= count($candidates) ?></div>
        <div class="stat-label">Total Candidates</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users-line"></i></div>
        <div class="stat-num"><?= $part_count ?></div>
        <div class="stat-label">Registered Participants</div>
    </div>
</div>

<!-- ── FILTER TABS ── -->
<div class="section-tabs">
    <button class="tab-btn active" data-filter="all">
        All Candidates <span class="count-pill"><?= count($candidates) ?></span>
    </button>
    <button class="tab-btn" data-filter="original">
        <i class="fas fa-star fa-xs"></i> Official
        <span class="count-pill"><?= $orig_count ?></span>
    </button>
    <button class="tab-btn" data-filter="participant">
        <i class="fas fa-user-check fa-xs"></i> Participants
        <span class="count-pill"><?= $part_count ?></span>
    </button>
</div>

<p class="instruction">Select Your Preferred Candidate to Cast Your Vote</p>

<!-- ── VOTING FORM ── -->
<form method="POST">
<div class="grid" id="candidateGrid">

<?php foreach ($candidates as $c):
    $pct     = $total_votes > 0 ? round(($c['count'] / $total_votes) * 100, 1) : 0;
    $is_part = $c['source'] === 'participant';
    $val     = $is_part ? 'p_' . $c['pid'] : 'o_' . htmlspecialchars($c['name']);
    $uid     = 'cand_' . md5($val);
?>
<div class="card" data-type="<?= $c['source'] ?>">
    <input type="radio" name="candidate" id="<?= $uid ?>" value="<?= $val ?>" required>
    <label for="<?= $uid ?>">
        <img class="card-img"
            src="<?= htmlspecialchars($c['img']) ?>"
            alt="<?= htmlspecialchars($c['name']) ?>"
            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($c['name']) ?>&size=400&background=1a2236&color=c9a84c&bold=true'">
        <div class="card-body">
            <?php if ($is_part): ?>
                <span class="type-badge participant"><i class="fas fa-user-check"></i> Participant</span>
            <?php else: ?>
                <span class="type-badge original"><i class="fas fa-star"></i> Official</span>
            <?php endif; ?>
            <div class="party-tag"><?= htmlspecialchars($c['party']) ?></div>
            <h2><?= htmlspecialchars($c['name']) ?></h2>
            <p><?= $is_part
                ? 'Community participant running in the MajesticVote 2026 Election.'
                : 'Dedicated to leadership, transparency, and community-first governance.' ?></p>
            <div class="vote-count">
                <?= number_format($c['count']) ?> votes
                <span style="color:var(--muted)">(<?= $pct ?>%)</span>
            </div>
            <div class="select-btn">Select Candidate</div>
        </div>
    </label>
</div>
<?php endforeach; ?>

</div><!-- /grid -->

<div class="submit-wrap">
    <button type="submit" name="vote"><i class="fas fa-check-double"></i> Cast Vote</button>
    <p class="disclaimer">🔒 Secure · Live Vote Counting</p>
</div>
</form>

<?php endif; ?>

<!-- ══════════════════════════════════════════
     PARTICIPANT REGISTRATION PORTAL
══════════════════════════════════════════ -->
<div class="section-divider">
    <div class="divider-label"><i class="fas fa-id-card"></i> Participant Registration Portal</div>
</div>

<div class="portal-wrapper">

    <!-- Hero -->
    <div class="portal-hero">
        <div class="portal-hero-text">
            <h2>Join the Democratic Process</h2>
            <p>Register as an official participant to appear as a <strong style="color:var(--gold)">votable candidate</strong> alongside official candidates. Upload your profile photo so voters can recognise you instantly.</p>
            <div class="portal-features">
                <span class="feat-pill"><i class="fas fa-shield-halved"></i> Verified Identity</span>
                <span class="feat-pill"><i class="fas fa-camera"></i> Profile Photo</span>
                <span class="feat-pill"><i class="fas fa-chart-line"></i> Live Vote Count</span>
                <span class="feat-pill"><i class="fas fa-lock"></i> Secure & Private</span>
            </div>
        </div>
        <div class="portal-badge-wrap">
            <div class="portal-icon-ring"><i class="fas fa-user-check"></i></div>
            <span>Official Portal</span>
        </div>
    </div>

    <?php if ($reg_success):
        $new_p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM participants WHERE id=$new_part_id"));
    ?>
    <!-- SUCCESS STATE -->
    <div class="reg-success">
        <?php if ($new_p && $new_p['profile_image']): ?>
        <img class="success-avatar"
            src="<?= $upload_url . htmlspecialchars($new_p['profile_image']) ?>"
            alt="Profile Photo">
        <?php endif; ?>
        <div class="success-ring">✓</div>
        <h2>Registration Successful!</h2>
        <p>Welcome, <strong style="color:var(--gold)"><?= htmlspecialchars($_SESSION['participant_name'] ?? '') ?></strong>!
        You are now a verified participant and your card is <strong style="color:var(--gold)">live in the voting grid</strong> above.</p>
        <div class="participant-id-badge">
            <i class="fas fa-id-badge"></i>
            Participant #<?= str_pad($new_part_id, 5, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <?php else: ?>
    <!-- REGISTRATION FORM -->
    <div class="reg-form-wrap">
        <h3><i class="fas fa-pen-nib"></i> Complete Your Registration</h3>

        <?php if ($reg_error): ?>
        <div class="alert alert-error">
            <i class="fas fa-circle-exclamation fa-lg"></i>
            <span><?= htmlspecialchars($reg_error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="regForm" enctype="multipart/form-data">
        <div class="form-grid">

            <!-- Full Name -->
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="full_name" placeholder="e.g. James Hopper"
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number</label>
                <input type="tel" name="phone" placeholder="+1 (555) 000-0000"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            </div>

            <!-- DOB -->
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Date of Birth</label>
                <input type="date" name="dob"
                    value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>"
                    max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
            </div>

            <!-- Gender -->
            <div class="form-group">
                <label><i class="fas fa-venus-mars"></i> Gender</label>
                <div class="gender-btns">
                    <button type="button" class="gender-btn" data-val="Male"><i class="fas fa-mars"></i> Male</button>
                    <button type="button" class="gender-btn" data-val="Female"><i class="fas fa-venus"></i> Female</button>
                    <button type="button" class="gender-btn" data-val="Other"><i class="fas fa-genderless"></i> Other</button>
                </div>
                <input type="hidden" name="gender" id="genderInput"
                    value="<?= htmlspecialchars($_POST['gender'] ?? '') ?>" required>
            </div>

            <!-- National ID -->
            <div class="form-group">
                <label><i class="fas fa-id-card"></i> National ID / Passport No.</label>
                <input type="text" name="id_number" placeholder="ID or Passport number"
                    value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>" required>
            </div>

            <!-- ★ PROFILE PHOTO UPLOAD ★ -->
            <div class="form-group full-width">
                <label>
                    <i class="fas fa-camera"></i> Profile Photo
                    <span style="color:var(--muted);font-size:10px;letter-spacing:0;text-transform:none;font-weight:400;margin-left:6px">
                        Optional · JPG / PNG / WEBP · Max 3 MB
                    </span>
                </label>
                <div class="img-upload-zone" id="uploadZone">
                    <input type="file" name="profile_image" id="profileImageInput"
                        accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                    <div class="upload-hint">
                        <strong>Click to upload</strong> or drag & drop your photo<br>
                        <span style="font-size:12px;opacity:.7">This photo will appear on your candidate card in the voting grid</span>
                    </div>
                </div>
                <!-- Live preview -->
                <div class="img-preview-wrap" id="previewWrap">
                    <img src="" alt="Preview" class="img-preview" id="imgPreview">
                    <div class="img-info">
                        <div class="fn" id="previewName">—</div>
                        <div class="fs" id="previewSize">—</div>
                    </div>
                    <button type="button" class="btn-remove" id="removeImg">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
            </div>

            <!-- Address -->
            <div class="form-group full-width">
                <label><i class="fas fa-location-dot"></i> Residential Address</label>
                <textarea name="address" placeholder="House/Flat No., Street, City, State, ZIP"
                    required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>

            <!-- Terms -->
            <div class="form-group full-width">
                <div class="terms-row">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">
                        I confirm that all information is accurate and truthful. I agree to the
                        <a href="#">Terms of Participation</a> and <a href="#">Privacy Policy</a>
                        of MajesticVote 2026. I understand my profile will be listed as a votable candidate.
                    </label>
                </div>
            </div>

        </div><!-- /form-grid -->

        <div class="reg-submit-row">
            <div class="security-note">
                <i class="fas fa-lock"></i> Your data is encrypted and never shared.
            </div>
            <button type="submit" name="register_participant" class="btn-register">
                <i class="fas fa-user-plus"></i> Register as Participant
            </button>
        </div>
        </form>
    </div><!-- /reg-form-wrap -->
    <?php endif; ?>

</div><!-- /portal-wrapper -->

</main>

<footer>MajesticVote © 2026 · Official Digital Election Platform</footer>

<script>
/* ── Gender toggle ── */
const genderBtns  = document.querySelectorAll('.gender-btn');
const genderInput = document.getElementById('genderInput');
if (genderInput?.value) {
    genderBtns.forEach(b => { if (b.dataset.val === genderInput.value) b.classList.add('active'); });
}
genderBtns.forEach(btn => btn.addEventListener('click', () => {
    genderBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    genderInput.value = btn.dataset.val;
}));

/* ── Gender validation on submit ── */
const regForm = document.getElementById('regForm');
if (regForm) {
    regForm.addEventListener('submit', e => {
        if (!genderInput.value) { e.preventDefault(); alert('Please select your gender.'); }
    });
}

/* ── Image upload preview ── */
const fileInput   = document.getElementById('profileImageInput');
const uploadZone  = document.getElementById('uploadZone');
const previewWrap = document.getElementById('previewWrap');
const imgPreview  = document.getElementById('imgPreview');
const previewName = document.getElementById('previewName');
const previewSize = document.getElementById('previewSize');
const removeBtn   = document.getElementById('removeImg');

const fmtBytes = b => b < 1048576 ? (b/1024).toFixed(1)+' KB' : (b/1048576).toFixed(1)+' MB';

function showPreview(file) {
    if (!file?.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
        imgPreview.src  = e.target.result;
        previewName.textContent = file.name;
        previewSize.textContent = fmtBytes(file.size);
        previewWrap.classList.add('visible');
    };
    reader.readAsDataURL(file);
}

fileInput?.addEventListener('change', () => fileInput.files[0] && showPreview(fileInput.files[0]));

removeBtn?.addEventListener('click', () => {
    fileInput.value = '';
    imgPreview.src  = '';
    previewWrap.classList.remove('visible');
});

/* Drag & drop */
uploadZone?.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone?.addEventListener('dragleave', ()  => uploadZone.classList.remove('dragover'));
uploadZone?.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showPreview(file);
    }
});

/* ── Candidate filter tabs ── */
const tabBtns = document.querySelectorAll('.tab-btn');
const cards   = document.querySelectorAll('#candidateGrid .card');

tabBtns.forEach(btn => btn.addEventListener('click', () => {
    tabBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const f = btn.dataset.filter;
    cards.forEach(c => c.style.display = (f === 'all' || c.dataset.type === f) ? '' : 'none');
}));
</script>
</body>
</html>