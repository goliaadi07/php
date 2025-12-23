<?php
// index.php - Real Estate Agent Registration
session_start();

// 1. DATABASE SETTINGS (Update with your credentials)
$host = "localhost";
$username = "root";
$password = "aditya29"; 
$dbname = "homebuilder_app";

// Connect
$pdo = null;
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'errors' => ['Database connection failed: ' . $e->getMessage()]]);
        exit;
    }
    die("Database connection failed: " . $e->getMessage());
}

// FORCE NEW REGISTRATION
if (isset($_GET['new']) && $_GET['new'] === '1') {
    unset($_SESSION['agent_reg_id']);
    header("Location: index.php");
    exit;
}

function jsonResponse(array $arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

$existing_id = isset($_SESSION['agent_reg_id']) ? (int)$_SESSION['agent_reg_id'] : 0;

/* ================== AJAX handler ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_step') {

    $step = isset($_POST['step']) ? (int)$_POST['step'] : 0;
    $submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : $existing_id;

    $get = function ($k) {
        return isset($_POST[$k]) ? trim((string)$_POST[$k]) : null;
    };

    $errors = [];
    $data   = [];

    /* ----- STEP 1: Personal ----- */
    if ($step === 1) {
        $data['first_name'] = $get('first_name');
        $data['last_name']  = $get('last_name');
        $data['email']      = $get('email');
        $data['phone_primary'] = $get('phone_primary');
        $data['phone_alt']  = $get('phone_alt');
        $data['preferred_contact_method'] = $get('preferred_contact_method');

        if ($data['first_name'] === '' || $data['last_name'] === '') $errors[] = 'First and last name are required.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($data['phone_primary'] === '') $errors[] = 'Primary phone is required.';
    }
    /* ----- STEP 2: Professional ----- */
    elseif ($step === 2) {
        $data['license_number']   = $get('license_number');
        $data['brokerage_name']   = $get('brokerage_name');
        $data['experience_level'] = $get('experience_level');
        $data['availability']     = $get('availability');
        $data['certifications']   = $get('certifications');

        if ($data['license_number'] === '' || $data['brokerage_name'] === '') $errors[] = 'License number and brokerage name are required.';
    }
    /* ----- STEP 3: Specialization ----- */
    elseif ($step === 3) {
        $data['primary_role']   = $get('primary_role');
        $data['service_areas']  = $get('service_areas');
        $data['property_types'] = $get('property_types');
        $data['price_ranges']   = $get('price_ranges');

        if ($data['primary_role'] === '') $errors[] = 'Please select your primary role.';
    }
    /* ----- STEP 4: Profile & Photos ----- */
    elseif ($step === 4) {
        $data['biography']       = $get('biography');
        $data['brand_statement'] = $get('brand_statement');
        $data['homes_closed_last_year'] = ($get('homes_closed_last_year') !== '') ? (int)$get('homes_closed_last_year') : null;
        $data['avg_days_to_close']      = ($get('avg_days_to_close') !== '') ? (int)$get('avg_days_to_close') : null;
        $data['languages']       = $get('languages');
        $data['website_url']     = $get('website_url');
        $data['linkedin_url']    = $get('linkedin_url');

        if ($data['biography'] === '') $errors[] = 'Professional biography is required.';

        // --- FILE UPLOAD LOGIC ---
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // 1. Profile Photo
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $newFileName = 'profile_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadDir . $newFileName)) {
                $data['profile_photo'] = $newFileName;
            }
        }

        // 2. Additional Photos
        if (isset($_FILES['additional_photos'])) {
            $uploadedFiles = [];
            $count = count($_FILES['additional_photos']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['additional_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['additional_photos']['name'][$i], PATHINFO_EXTENSION);
                    $newFileName = 'gallery_' . time() . '_' . $i . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['additional_photos']['tmp_name'][$i], $uploadDir . $newFileName)) {
                        $uploadedFiles[] = $newFileName;
                    }
                }
            }
            if (!empty($uploadedFiles)) $data['additional_photos'] = implode(',', $uploadedFiles);
        }
    }
    /* ----- STEP 5: Finalize ----- */
    elseif ($step === 5) {
        $data['agree_terms'] = ($get('agree_terms') === '1') ? 1 : 0;
        $data['digital_signature'] = $get('digital_signature');

        if ($data['agree_terms'] !== 1) $errors[] = 'You must agree to the Terms and Conditions.';
        if (empty($data['digital_signature'])) $errors[] = 'Please provide your electronic signature.';
    } else {
        $errors[] = 'Invalid step.';
    }

    if (!empty($errors)) {
        jsonResponse(['ok' => false, 'errors' => $errors]);
    }

    try {
        if ($submission_id > 0) {
            // UPDATE EXISTING
            $set    = [];
            $params = [];
            foreach ($data as $k => $v) {
                $set[]          = "`$k` = :$k";
                $params[":$k"]  = $v;
            }
            $set[]           = "last_completed_step = GREATEST(last_completed_step, :step)";
            $params[':step'] = $step;
            $params[':id']   = $submission_id;

            $sql  = "UPDATE agentregister SET " . implode(', ', $set) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // INSERT NEW (Generate Tracking ID Here)
            $tracking_id = 'AGT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)); // Generate ID
            $data['tracking_id'] = $tracking_id;

            $cols    = [];
            $holders = [];
            $params  = [];
            foreach ($data as $k => $v) {
                $cols[]        = "`$k`";
                $holders[]     = ":$k";
                $params[":$k"] = $v;
            }
            $cols[]                     = "last_completed_step";
            $holders[]                  = ":last_completed_step";
            $params[':last_completed_step'] = $step;

            $sql  = "INSERT INTO agentregister (" . implode(',', $cols) . ") VALUES (" . implode(',', $holders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $submission_id = (int)$pdo->lastInsertId();
            $_SESSION['agent_reg_id'] = $submission_id;
        }

        $response = [
            'ok'            => true,
            'submission_id' => $submission_id,
            'step'          => $step,
        ];

        // If Step 5 (Final), fetch the tracking ID to send back to the UI
        if ($step === 5) {
            $stmt = $pdo->prepare("SELECT tracking_id FROM agentregister WHERE id = ?");
            $stmt->execute([$submission_id]);
            $response['tracking_id'] = $stmt->fetchColumn(); // Send ID to frontend
            
            unset($_SESSION['agent_reg_id']);
        }

        jsonResponse($response);

    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'errors' => ['DB error: ' . $e->getMessage()]]);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Real Estate Agent Registration</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eaf7ff;
  --muted:#6b7280;
  --dark:#020617;
  --blue:#2563eb;
  --teal:#059669;
  --purple:#7c3aed;
  --purple-light:#f3e8ff;
  --purple-border:#d8b4fe;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Inter,system-ui,Arial,sans-serif;
  background:linear-gradient(180deg,#f5fbff,var(--bg));
  color:#0f172a;
}
.container{
  max-width:1100px;
  margin:32px auto;
  padding:0 24px 40px;
}

/* BACK TO HOME */
.top-bar{
  display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;
}
.back-home{
  padding:8px 14px; border-radius:999px; border:1px solid #e5e7eb; background:#fff;
  display:inline-flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;
  text-decoration:none; color:#111827; transition:all 0.2s;
}
.back-home:hover{background:#f9fafb;}

/* Header */
.header{text-align:center;margin-bottom:8px}
.header h1{font-size:24px;font-weight:700;margin-bottom:4px}
.header p{color:var(--muted);font-size:14px}

/* Progress */
.top-progress{
  height:8px; background:#d4d4d8; border-radius:999px; margin:20px 0 16px; overflow:hidden;
}
.top-progress .fill{
  height:100%; width:0; background:#020617; transition:width .3s ease;
}

/* Steps */
.steps{
  display:flex; justify-content:space-between; align-items:center; margin:0 40px 22px;
}
.step{flex:1;text-align:center;font-size:13px;}
.stepIcon{
  width:60px;height:60px;border-radius:50%; background:#fff;border:2px solid #e5e7eb;
  display:flex;align-items:center;justify-content:center; margin:0 auto 6px;
  box-shadow:0 10px 30px rgba(15,23,42,0.07); transition:all 0.3s;
}
.stepIcon span{font-size:24px;}
.stepLabel{color:var(--muted);}
.step.active .stepIcon{
  background:linear-gradient(180deg,#0f766e,#0ea5e9); border-color:transparent;color:#fff;transform:translateY(-6px);
}
.step.done .stepIcon{
  background:#22c55e;border-color:transparent;color:#fff;transform:translateY(-4px);
}

/* Card */
.card{
  background:#fff; border-radius:16px; margin:0 40px;
  box-shadow:0 18px 50px rgba(15,23,42,0.12); overflow:hidden;
}
.card-header{
  display:flex; align-items:center; padding:18px 26px; color:#fff;
  background:linear-gradient(90deg,#059669,#0ea5e9);
}
.card-header.blue{background:linear-gradient(90deg,#2563eb,#4f46e5);}
.card-header.green{background:linear-gradient(90deg,#059669,#16a34a);}
.card-header.teal{background:linear-gradient(90deg,#0f766e,#22c55e);}
.card-header.purple{background:linear-gradient(90deg,#7c3aed,#a855f7);}
.card-header-icon{
  width:48px;height:48px;border-radius:18px; background:rgba(255,255,255,.14);
  display:flex;align-items:center;justify-content:center; margin-right:14px;
}
.card-header-icon span{font-size:24px;}
.card-header-text .title{font-size:18px;font-weight:600;}
.card-header-text .subtitle{font-size:14px;margin-top:2px;}

/* Body */
.card-body{
  padding:24px 32px 28px; background:linear-gradient(180deg,#ffffff,#f9fbff);
}
.form-row{display:flex;gap:16px;margin-bottom:14px;}
.col{flex:1}
.input label{
  display:block;margin-bottom:6px;font-weight:600;color:#111827;font-size:14px;
}
.input input,.input select,.input textarea{
  width:100%;padding:10px 12px;border-radius:10px; border:1px solid #e5e7eb;background:#f9fafb;font-size:14px;
}
.input textarea{min-height:110px;resize:vertical;}
.help-text {
    background-color: #e6fffa; color: #2c7a7b; border: 1px solid #b2f5ea;
    padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-top: 20px; line-height: 1.5;
}
.match-client {
    background-color: #e6fffa; color: #2c7a7b; border: 1px solid #b2f5ea;
    padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-top: 20px; line-height: 1.5;
}

/* PHOTO UPLOAD CSS */
.photo-section-title {
    font-size: 16px; font-weight: 700; color: #111827; margin: 30px 0 15px; 
    display: flex; align-items: center; gap: 8px; border-top:1px solid #e5e7eb; padding-top:20px;
}
.photo-row { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 25px; }
.upload-box {
    border: 2px dashed #d1d5db; border-radius: 10px; background: #fff;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; cursor: pointer; position: relative; color: #6b7280; transition: all 0.2s;
}
.upload-box:hover { border-color: #2563eb; background: #f0f9ff; color: #2563eb; }
.upload-box.has-file { border-color: #059669; background: #f0fdf4; color: #059669; }

.upload-box i { font-size: 24px; margin-bottom: 8px; color: inherit; }
.upload-box span { font-size: 13px; font-weight: 600; display:block; }
.upload-box .file-name { font-size: 12px; font-weight: 600; color: #166534; margin-bottom: 4px; word-break: break-all; }
.upload-box .replace-text { font-size: 11px; text-decoration: underline; color: #2563eb; font-weight: 500; }
.upload-box input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index:10; }

.upload-box.square { width: 140px; height: 140px; flex-shrink: 0; }
.upload-box.wide { width: 100%; height: 110px; }

.photo-desc { padding-top: 10px; }
.photo-desc h4 { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #111827; }
.photo-desc p { margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5; }

.blue-tip {
    background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a;
    padding: 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;
    display: flex; gap: 8px; align-items: center;
}

/* Pills & Chips */
.pill-row{display:flex;flex-direction:column;gap:10px;margin-bottom:16px;}
.pill-option{
  border-radius:14px;border:1px solid #e5e7eb; padding:10px 14px;
  display:flex;align-items:flex-start;gap:10px; cursor:pointer;background:#fff;
}
.pill-option .dot{
  width:16px;height:16px;border-radius:999px; border:2px solid #e5e7eb;margin-top:3px;
}
.pill-option .pill-main{font-size:14px;font-weight:600;}
.pill-option .pill-sub{font-size:13px;color:#6b7280;}
.pill-option.selected{ border-color:#22c55e;background:#ecfdf5; }
.pill-option.selected .dot{border-color:#22c55e;background:#22c55e;}
.chip-grid{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;}
.chip{
  padding:7px 13px;border-radius:999px;border:1px solid #e5e7eb; background:#fff;font-size:13px;cursor:pointer;
}
.chip.selected{ background:#ecfdf5;border-color:#22c55e;color:#14532d; }

/* Messages */
#msg,#err{
  padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:13px;
}
#msg{background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;}
#err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
.hidden{display:none !important;}

/* Final Step */
.final-row{
  border-radius:12px; border:1px solid #e5e7eb; padding:12px 16px;
  background:#faf5ff; display:flex; align-items:flex-start; gap:10px; font-size:14px;
}
.final-row input[type="checkbox"]{width:18px;height:18px;margin-top:2px;cursor:pointer;}

/* Signature Box (Purple Theme) */
.signature-box {
    display: none; /* Hidden initially */
    margin-top: 20px;
    padding: 20px;
    border-radius: 12px;
    background: var(--purple-light);
    border: 1px solid var(--purple-border);
    animation: fadeIn 0.4s ease;
}
@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }

.sig-label {
    display: block; font-weight: 600; color: #6b21a8; margin-bottom: 8px; font-size: 14px;
}
.sig-label i { margin-right:6px; }
.sig-input {
    width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--purple-border);
    font-size: 16px; font-family: cursive; color: #4c1d95;
}
.sig-note {
    font-size: 12px; color: #7c3aed; margin-top: 8px;
}

/* Modal Overlay */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none;
    align-items: center; justify-content: center; z-index: 2000;
}
.modal-content {
    background: #fff; width: 90%; max-width: 600px; max-height: 85vh;
    border-radius: 16px; overflow-y: auto; padding: 0;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex; flex-direction: column;
}
.modal-header {
    padding: 20px 24px; border-bottom: 1px solid #e5e7eb;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-header h3 { margin: 0; font-size: 18px; color: #111827; }
.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
.modal-body { padding: 24px; overflow-y: auto; }
.tc-item { margin-bottom: 16px; }
.tc-item h4 { margin: 0 0 4px; font-size: 15px; color: #374151; font-weight: 600; }
.tc-item p { margin: 0; font-size: 14px; color: #6b7280; line-height: 1.5; }
.modal-footer {
    padding: 16px 24px; border-top: 1px solid #e5e7eb; background: #f9fafb;
    display: flex; justify-content: flex-end;
}
.btn-close-modal {
    background: #111827; color: #fff; border: none; padding: 8px 16px;
    border-radius: 6px; cursor: pointer; font-weight: 500;
}

/* SUCCESS MODAL SPECIFIC */
.success-icon {
    width: 70px; height: 70px; background: #ecfdf5; border-radius: 50%;
    color: #059669; font-size: 32px; display: flex; align-items: center;
    justify-content: center; margin: 0 auto 20px;
}
.success-actions-row {
    display: flex; gap: 15px; justify-content: center; width: 100%; margin-top:25px;
}
.btn-return {
    background: #fff; border: 1px solid #e5e7eb; color: #374151;
    padding: 10px 20px; border-radius: 999px; cursor: pointer; font-weight: 600;
}
.btn-return:hover { background:#f9fafb; }
.btn-resubmit {
    background: #059669; border: 1px solid #059669; color: #fff;
    padding: 10px 20px; border-radius: 999px; cursor: pointer; font-weight: 600;
}
.btn-resubmit:hover { background:#047857; }
.tracking-box {
    background: #eff6ff;
    border: 1px dashed #2563eb;
    color: #1e3a8a;
    padding: 10px;
    border-radius: 8px;
    margin: 20px 0;
    font-size: 14px;
    font-weight: 500;
}
.tracking-id-text {
    font-family: monospace;
    font-size: 16px;
    font-weight: 700;
    color: #2563eb;
    letter-spacing: 1px;
}
.success-steps { text-align: left; margin: 15px 0; font-size: 14px; color: #374151; }
.success-steps li { margin-bottom: 8px; }

/* Actions */
.actions{
  display:flex; justify-content:space-between; align-items:center; padding:16px 40px 0;
}
.btn{
  padding:11px 18px;border-radius:999px; border:1px solid #e5e7eb; background:#fff;
  font-weight:600;font-size:14px; cursor:pointer;min-width:170px;
}
.btn.primary{
  background:linear-gradient(90deg,#0f766e,#22c55e); color:#fff;border-color:transparent;
}
.btn.primary.disabled, .btn[disabled]{opacity:.55;cursor:not-allowed;}
.btn.secondary{background:#f9fafb;}

@media(max-width:900px){
  .steps{margin:0 14px 18px;} .card{margin:0 14px;} .card-body{padding:20px 18px 24px;}
  .form-row{flex-direction:column;} .actions{flex-direction:column;gap:10px;padding:16px 24px 0;}
}
</style>
</head>
<body>

<div id="termsModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Terms and Conditions</h3>
      <button class="modal-close" id="closeTermsTop">&times;</button>
    </div>
    <div class="modal-body">
      <div class="tc-item">
        <h4>1. Professional Conduct</h4>
        <p>As a registered agent in our directory, you agree to uphold the highest standards of professional conduct and adhere to the National Association of Realtors Code of Ethics.</p>
      </div>
      <div class="tc-item">
        <h4>2. License Verification</h4>
        <p>You certify that your real estate license is current, active, and in good standing. We reserve the right to verify your license with the state real estate board.</p>
      </div>
      <div class="tc-item">
        <h4>3. Information Accuracy</h4>
        <p>You confirm that all information provided in this registration is accurate and complete. You agree to update your profile promptly if any information changes.</p>
      </div>
      <div class="tc-item">
        <h4>4. Client Leads</h4>
        <p>Client leads provided through our platform remain your responsibility. You agree to respond promptly and professionally to all client inquiries.</p>
      </div>
      <div class="tc-item">
        <h4>5. Profile Display</h4>
        <p>Your profile, including contact information and professional details, will be displayed publicly in our real estate professional directory to potential clients.</p>
      </div>
      <div class="tc-item">
        <h4>6. Removal Policy</h4>
        <p>We reserve the right to remove any agent from our directory for violations of these terms, license suspension/revocation, or unethical conduct.</p>
      </div>
      <div class="tc-item">
        <h4>7. No Guarantee</h4>
        <p>Registration in our directory does not guarantee client leads or business opportunities. This is a platform to connect agents with potential clients.</p>
      </div>
      <div class="tc-item">
        <p style="font-weight:600; color:#4b5563;">Questions? Contact our support team if you have questions about these terms.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-close-modal" id="closeTermsBtn">Close</button>
    </div>
  </div>
</div>

<div id="successModal" class="modal-overlay">
  <div class="modal-content" style="text-align:center; padding: 40px; max-width: 550px;">
    <div class="success-icon">✓</div>
    <h2 style="margin:0 0 10px; font-size:24px;">Registration Submitted!</h2>
    <p style="color:#666; margin-bottom:15px; line-height:1.5;">
        Thank you for joining our network of real estate professionals! Our team will review your application and contact you within <strong>48-72 hours</strong> to complete your profile setup.
    </p>

    <div class="tracking-box">
        Your Tracking ID: <span id="finalTrackingId" class="tracking-id-text">Loading...</span>
    </div>

    <div style="text-align:left; background:#f9fafb; padding:15px; border-radius:8px;">
        <strong style="display:block;margin-bottom:8px;color:#111827;">What's next:</strong>
        <ul style="margin:0; padding-left:20px; color:#4b5563; font-size:14px; line-height:1.6;">
            <li>Profile verification and approval</li>
            <li>Access to our client matching system</li>
            <li>Welcome to our professional network</li>
        </ul>
    </div>

    <div class="success-actions-row">
        <button class="btn-return" onclick="window.location.href='index.php'">Return to Home</button>
        <button class="btn-resubmit" onclick="window.location.href='?new=1'">Submit Another</button>
    </div>
  </div>
</div>

<div class="container">

  <div class="top-bar">
    <a href="/" class="back-home"><span class="arrow">←</span> Back to Home</a>
    <div></div>
  </div>

  <div class="header">
    <h1>Real Estate Agent Registration</h1>
    <p>Join our network of trusted professionals and connect with qualified buyers and sellers</p>
  </div>

  <div class="top-progress" role="progressbar">
    <div id="progressFill" class="fill" style="width:0"></div>
  </div>

  <div class="steps">
    <div class="step active" data-step="1">
      <div class="stepIcon"><span>👤</span></div>
      <div class="stepLabel">Personal</div>
    </div>
    <div class="step" data-step="2">
      <div class="stepIcon"><span>📊</span></div>
      <div class="stepLabel">Professional</div>
    </div>
    <div class="step" data-step="3">
      <div class="stepIcon"><span>⭐</span></div>
      <div class="stepLabel">Specialization</div>
    </div>
    <div class="step" data-step="4">
      <div class="stepIcon"><span>📝</span></div>
      <div class="stepLabel">Profile</div>
    </div>
    <div class="step" data-step="5">
      <div class="stepIcon"><span>✅</span></div>
      <div class="stepLabel">Finalize</div>
    </div>
  </div>

  <div class="card">
    <div id="cardHeader" class="card-header teal">
      <div class="card-header-icon"><span>👤</span></div>
      <div class="card-header-text">
        <div id="cardTitle" class="title">Personal Information</div>
        <div id="cardSubtitle" class="subtitle">Your contact information for potential clients</div>
      </div>
    </div>

    <div class="card-body">
      <div id="msg" class="hidden"></div>
      <div id="err" class="hidden"></div>

      <form id="agentForm" method="post" novalidate enctype="multipart/form-data">

        <div class="panel" data-step="1">
          <div class="form-row">
            <div class="col input">
              <label for="first_name">First Name *</label>
              <input id="first_name" name="first_name" type="text">
            </div>
            <div class="col input">
              <label for="last_name">Last Name *</label>
              <input id="last_name" name="last_name" type="text">
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="email">Email Address *</label>
              <input id="email" name="email" type="email">
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="phone_primary">Phone Number *</label>
              <input id="phone_primary" name="phone_primary" type="text" placeholder="(555) 123-4567">
            </div>
            <div class="col input">
              <label for="phone_alt">Alternate Phone</label>
              <input id="phone_alt" name="phone_alt" type="text" placeholder="(555) 987-6543">
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="preferred_contact_method">Preferred Contact Method</label>
              <select id="preferred_contact_method" name="preferred_contact_method">
                <option value="">Select preferred contact method</option>
                <option>Email</option>
                <option>Phone</option>
                <option>Text</option>
                <option>Any Method</option>

              </select>
            </div>
          </div>
          <div class="help-text">
            <strong>Privacy:</strong> Your contact information will be displayed to potential clients in our directory.
          </div>
        </div>

        <div class="panel hidden" data-step="2">
          <div class="form-row">
            <div class="col input">
              <label for="license_number">Real Estate License Number *</label>
              <input id="license_number" name="license_number" type="text" placeholder="RE-123456">
            </div>
            <div class="col input">
              <label for="brokerage_name">Brokerage Name *</label>
              <input id="brokerage_name" name="brokerage_name" type="text" placeholder="Smith Realty Group">
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="experience_level">Years of Experience</label>
              <select id="experience_level" name="experience_level">
                <option value="">Select experience level</option>
                <option>0-2 years</option>
                <option>3-5 years</option>
                <option>6-10 years</option>
                <option>11-15 years</option>
                <option>16-20 years</option>
                <option>20+ years</option>
              </select>
            </div>
            <div class="col input">
              <label for="availability">Availability</label>
              <select id="availability" name="availability">
                <option value="">Select availability</option>
                <option>Taking new clients now</option>
                <option>Available in 1-2 weeks</option>
                <option>Available within 1 month</option>
                <option>Limited availability</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="certifications">Certifications &amp; Designations</label>
              <textarea id="certifications" name="certifications" placeholder="e.g., CRS, ABR, GRI, SRES..."></textarea>
              <div style="font-size:12px;color:#6b7280;margin-top:3px;">List any professional certifications or special designations you hold.</div>
            </div>
          </div>
          <div class="help-text">
            <strong>Verification:</strong> We may verify your license information with the state real estate board.
          </div>
        </div>

        <div class="panel hidden" data-step="3">
          <div class="input" style="margin-bottom:8px;">
            <label>I primarily work as a:</label>
          </div>
          <div class="pill-row" id="primaryRoleRow">
            <div class="pill-option" data-value="buyers_agent">
              <div class="dot"></div>
              <div>
                <div class="pill-main">Buyer’s Agent</div>
                <div class="pill-sub">I help clients find and purchase homes</div>
              </div>
            </div>
            <div class="pill-option" data-value="sellers_agent">
              <div class="dot"></div>
              <div>
                <div class="pill-main">Seller’s Agent (Listing Agent)</div>
                <div class="pill-sub">I help homeowners sell their properties</div>
              </div>
            </div>
            <div class="pill-option" data-value="full_service">
              <div class="dot"></div>
              <div>
                <div class="pill-main">Full-Service Agent (Both)</div>
                <div class="pill-sub">I work with both buyers and sellers</div>
              </div>
            </div>
            <div class="pill-option" data-value="custom_build">
              <div class="dot"></div>
              <div>
                <div class="pill-main">Custom build</div>
                <div class="pill-sub">I specialize in custom home building</div>
              </div>
            </div>
            
          </div>
          <input type="hidden" name="primary_role" id="primary_role">

          <div class="input"><label>Service Areas</label></div>
          <div class="chip-grid" data-name="service_areas">
            <div class="chip" data-value="Downtown">Downtown</div>
            <div class="chip" data-value="North Side">North Side</div>
            <div class="chip" data-value="South Side">South Side</div>
            <div class="chip" data-value="East Side">East Side</div>
            <div class="chip" data-value="West Side">West Side</div>
            <div class="chip" data-value="Suburbs">Suburbs</div>
            <div class="chip" data-value="Rural Areas">Rural Areas</div>
            <div class="chip" data-value="Waterfront">Waterfront</div>
            <div class="chip" data-value="Mountain Areas">Mountain Areas</div>
          </div>

          <div class="input"><label>Property Types</label></div>
          <div class="chip-grid" data-name="property_types">
            <div class="chip" data-value="Single Family Homes">Single Family Homes</div>
            <div class="chip" data-value="Condos">Condos</div>
            <div class="chip" data-value="Luxury Homes">Luxury Homes</div>
            <div class="chip" data-value="Land/Lots">Land/Lots</div>
            <div class="chip" data-value="Townhouses">Townhouses</div>
            <div class="chip" data-value="Multi-Family">Multi-Family</div>
            <div class="chip" data-value="New Construction">New Construction</div>
            <div class="chip" data-value="Investment Properties">Investment Properties</div>
          </div>

          <div class="input"><label>Price Ranges</label></div>
          <div class="chip-grid" data-name="price_ranges">
            <div class="chip" data-value="Under $200k">Under $200k</div>
            <div class="chip" data-value="$200k - $400k">$200k - $400k</div>
            <div class="chip" data-value="$400k - $600k">$400k - $600k</div>
            <div class="chip" data-value="$600k - $800k">$600k - $800k</div>
            <div class="chip" data-value="$800k - $1M">$800k - $1M</div>
            <div class="chip" data-value="$1M - $2M">$1M - $2M</div>
            <div class="chip" data-value="Over $2M">Over $2M</div>
          </div>
          <div class="match-client">
            <strong>Match with clients:</strong> Select all that apply to maximize your client matches.
          </div>
        </div>

        <div class="panel hidden" data-step="4">
          <div class="form-row">
            <div class="col input">
              <label for="biography">Professional Biography *</label>
              <textarea id="biography" name="biography" placeholder="Share your professional story..."></textarea>
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="brand_statement">Personal Brand Statement</label>
              <textarea id="brand_statement" name="brand_statement" placeholder="Your unique value proposition..."></textarea>
            </div>
          </div>

          <div class="photo-section-title"><i class="fas fa-camera"></i> Professional Photos</div>
          
          <div class="photo-row">
              <div class="upload-box square">
                  <i class="fas fa-arrow-up-from-bracket"></i>
                  <span class="main-text">Upload</span>
                  <input type="file" name="profile_photo" accept="image/*">
              </div>
              <div class="photo-desc">
                  <h4>Professional headshot</h4>
                  <p>A clear, professional photo helps clients connect with you. Recommended size: 400x400px</p>
              </div>
          </div>

          <div style="margin-bottom:20px;">
              <label class="photo-desc" style="display:block;margin-bottom:8px;font-weight:600;">Additional Photos (Optional)</label>
              <div class="upload-box wide">
                  <i class="fas fa-arrow-up-from-bracket"></i>
                  <span class="main-text">Upload Additional Photos</span>
                  <span class="sub-text">Office, team, or property photos (0/6)</span>
                  <input type="file" name="additional_photos[]" multiple accept="image/*">
              </div>
          </div>

          <div class="blue-tip" style="margin-bottom:25px;">
              <strong>Tip:</strong> Photos of your office, team, or successful projects help build trust with potential clients.
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="homes_closed_last_year">Homes Closed Last Year</label>
              <input id="homes_closed_last_year" name="homes_closed_last_year" type="number" min="0" placeholder="e.g., 25">
            </div>
            <div class="col input">
              <label for="avg_days_to_close">Average Days to Close</label>
              <input id="avg_days_to_close" name="avg_days_to_close" type="number" min="0" placeholder="e.g., 45">
            </div>
          </div>
          <div class="input"><label>Languages Spoken</label></div>
          <div class="chip-grid" data-name="languages">
            <div class="chip" data-value="English">English</div>
            <div class="chip" data-value="Spanish">Spanish</div>
            <div class="chip" data-value="French">French</div>
            <div class="chip" data-value="Mandarin">Mandarin</div>
            <div class="chip" data-value="German">German</div>
            <div class="chip" data-value="Italian">Italian</div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="website_url">Personal Website or Agent Profile</label>
              <input id="website_url" name="website_url" type="url" placeholder="https://www.yourwebsite.com">
            </div>
          </div>
          <div class="form-row">
            <div class="col input">
              <label for="linkedin_url">LinkedIn Profile</label>
              <input id="linkedin_url" name="linkedin_url" type="url" placeholder="https://www.linkedin.com/in/yourprofile">
            </div>
          </div>
          <div class="help-text"><strong>Stand out:</strong> A compelling profile helps you connect with more clients.</div>
        </div>

        <div class="panel hidden" data-step="5">
          <div class="final-row">
            <input type="checkbox" id="agree_terms" name="agree_terms" value="1">
            <label for="agree_terms" style="cursor:pointer;">
              I agree to the
              <a href="#" id="openTermsLink" style="color:#7c3aed;font-weight:600;">Terms and Conditions</a>
              and confirm that all information provided is accurate and up-to-date.
            </label>
          </div>

          <div id="signatureSection" class="signature-box">
             <label class="sig-label">🖊️ Your Signature *</label>
             <input type="text" id="digital_signature" name="digital_signature" class="sig-input" placeholder="Type your full name as signature">
             <div class="sig-note">By typing your name above, you are electronically signing this registration form.</div>
          </div>

          <div class="final-tip" style="margin-top:20px;">
            <strong>Final Step:</strong> Review your information and submit your registration.
            We'll review and contact you within 48–72 hours.
          </div>
        </div>

      </form>
    </div>
  </div>

  <div class="actions">
    <button id="prevBtn" class="btn secondary" style="visibility:hidden;">← Previous</button>
    <button id="nextBtn" class="btn primary">Next Step →</button>
    <button id="submitBtn" class="btn primary disabled hidden" disabled>Submit Registration</button>
  </div>

</div>

<script>
(function(){
  const qs  = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  // DOM Elements
  const panels       = qsa('.panel');
  const steps        = qsa('.step');
  const progressFill = qs('#progressFill');
  const prevBtn      = qs('#prevBtn');
  const nextBtn      = qs('#nextBtn');
  const submitBtn    = qs('#submitBtn');
  const msgBox       = qs('#msg');
  const errBox       = qs('#err');
  const cardHeader   = qs('#cardHeader');
  const cardTitle    = qs('#cardTitle');
  const cardSubtitle = qs('#cardSubtitle');
  const agreeTerms   = qs('#agree_terms');
  const sigSection   = qs('#signatureSection');
  const sigInput     = qs('#digital_signature');
  const form         = qs('#agentForm');

  // Modal Elements
  const termsModal   = qs('#termsModal');
  const openTerms    = qs('#openTermsLink');
  const closeTerms1  = qs('#closeTermsTop');
  const closeTerms2  = qs('#closeTermsBtn');
  const successModal = qs('#successModal');
  const finalId      = qs('#finalTrackingId');

  let current = 0;
  const last  = panels.length - 1;
  let submission_id = <?php echo $existing_id; ?>;

  const stepMeta = [
    { title:'Personal Information',      subtitle:'Your contact information for potential clients',            headerClass:'teal',   icon:'👤' },
    { title:'Professional Credentials', subtitle:'Your license and brokerage information',                   headerClass:'blue',   icon:'📊' },
    { title:'Specialization & Service Areas', subtitle:'What type of agent are you and where do you serve?', headerClass:'green',  icon:'⭐' },
    { title:'Professional Profile',      subtitle:'Tell clients about your background and achievements',      headerClass:'blue',   icon:'📝' },
    { title:'Finalize Registration',     subtitle:'Review and sign your agreement',                           headerClass:'purple', icon:'✅' }
  ];

  function setHeader(i){
    const meta = stepMeta[i];
    cardTitle.textContent = meta.title;
    cardSubtitle.textContent = meta.subtitle;
    cardHeader.className = 'card-header ' + meta.headerClass;
    cardHeader.querySelector('.card-header-icon span').textContent = meta.icon;
  }

  function showStep(i){
    panels.forEach((p,idx)=>p.classList.toggle('hidden', idx!==i));
    steps.forEach((s,idx)=>{
      s.classList.toggle('active', idx===i);
      s.classList.toggle('done', idx<i);
    });
    progressFill.style.width = ((i)/last)*100 + '%';
    prevBtn.style.visibility = i===0 ? 'hidden' : 'visible';
    nextBtn.classList.toggle('hidden', i===last);
    submitBtn.classList.toggle('hidden', i!==last);
    setHeader(i);
  }

  function showError(msg){
    errBox.textContent = msg; errBox.classList.remove('hidden'); msgBox.classList.add('hidden');
  }
  function showInfo(msg){
    msgBox.textContent = msg; msgBox.classList.remove('hidden'); errBox.classList.add('hidden');
  }

  // --- Logic for Terms & Signature ---
  
  // 1. Modal Logic
  function openModal(e) { e.preventDefault(); termsModal.style.display = 'flex'; }
  function closeModal() { termsModal.style.display = 'none'; }
  
  if(openTerms) openTerms.addEventListener('click', openModal);
  if(closeTerms1) closeTerms1.addEventListener('click', closeModal);
  if(closeTerms2) closeTerms2.addEventListener('click', closeModal);
  
  // Close on outside click
  window.addEventListener('click', (e) => {
    if (e.target === termsModal) closeModal();
  });

  // 2. Checkbox & Signature Logic
  if (agreeTerms){
    agreeTerms.addEventListener('change', ()=>{
      const isChecked = agreeTerms.checked;
      
      if(isChecked){
        sigSection.style.display = 'block'; // Show Signature
        submitBtn.disabled = false;
        submitBtn.classList.remove('disabled');
      } else {
        sigSection.style.display = 'none';  // Hide Signature
        submitBtn.disabled = true;
        submitBtn.classList.add('disabled');
        sigInput.value = ''; // clear if unchecked
      }
    });
  }

  // --- UI helpers (chips & pills) ---
  qsa('#primaryRoleRow .pill-option').forEach(opt=>{
    opt.addEventListener('click', ()=>{
      qsa('#primaryRoleRow .pill-option').forEach(o=>o.classList.remove('selected'));
      opt.classList.add('selected');
      qs('#primary_role').value = opt.dataset.value;
    });
  });

  qsa('.chip-grid').forEach(grid=>{
    grid.addEventListener('click', e=>{
      const chip = e.target.closest('.chip');
      if (!chip) return;
      chip.classList.toggle('selected');
    });
  });
  
  // File Upload Logic (Display Name & Replace)
  qsa('input[type="file"]').forEach(input => {
      input.addEventListener('change', function() {
          const box = this.closest('.upload-box');
          const fileCount = this.files.length;
          
          if (fileCount > 0) {
              box.classList.add('has-file');
              
              // Clear previous "Upload" text if any, show replace logic
              let nameDisplay = box.querySelector('.file-name');
              let replaceText = box.querySelector('.replace-text');
              
              if(!nameDisplay) {
                 nameDisplay = document.createElement('div');
                 nameDisplay.className = 'file-name';
                 box.insertBefore(nameDisplay, input);
              }
              if(!replaceText) {
                 replaceText = document.createElement('div');
                 replaceText.className = 'replace-text';
                 replaceText.textContent = "Replace";
                 box.insertBefore(replaceText, input);
              }

              // Update Content
              const fileName = fileCount === 1 ? this.files[0].name : `${fileCount} files selected`;
              nameDisplay.textContent = fileName;
              
              // Hide original "Upload" label if it exists
              const originalSpan = box.querySelector('.main-text');
              if(originalSpan) originalSpan.style.display = 'none';
              const subText = box.querySelector('.sub-text');
              if(subText) subText.style.display = 'none';
              
              const icon = box.querySelector('i');
              if(icon) icon.className = 'fas fa-check-circle'; // Change icon to check
          }
      });
  });

  function validateStep(idx){
    if (idx === 0){
      const fn = qs('#first_name').value.trim();
      const ln = qs('#last_name').value.trim();
      const em = qs('#email').value.trim();
      const ph = qs('#phone_primary').value.trim();
      if (!fn || !ln){ showError('First and last name are required.'); return false; }
      if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(em)){ showError('Please enter a valid email address.'); return false; }
      if (!ph){ showError('Primary phone number is required.'); return false; }
    }
    if (idx === 1){
      const lic = qs('#license_number').value.trim();
      const brk = qs('#brokerage_name').value.trim();
      if (!lic || !brk){ showError('License number and brokerage name are required.'); return false; }
    }
    if (idx === 2){
      const pr = qs('#primary_role').value.trim();
      if (!pr){ showError('Please choose your primary role.'); return false; }
    }
    if (idx === 3){
      const bio = qs('#biography').value.trim();
      if (!bio){ showError('Professional biography is required.'); return false; }
    }
    if (idx === 4){
      if (!agreeTerms.checked){ showError('You must agree to the Terms and Conditions.'); return false; }
      const sig = sigInput.value.trim();
      if (!sig) { showError('Please provide your electronic signature.'); return false; }
    }
    return true;
  }

  function collectMultiFromGrid(attrName){
    const grid = qsa('.chip-grid').find(g => g.dataset.name === attrName);
    if (!grid) return '';
    return qsa('.chip.selected', grid).map(c=>c.dataset.value).join(', ');
  }

  function collectPayload(idx){
    const fd = new FormData(form);
    fd.append('action','save_step');
    fd.append('step', idx+1);
    if (submission_id) fd.append('submission_id', submission_id);

    // Override manual fields
    if (idx === 2){
      fd.set('primary_role', qs('#primary_role').value || '');
      fd.set('service_areas', collectMultiFromGrid('service_areas'));
      fd.set('property_types', collectMultiFromGrid('property_types'));
      fd.set('price_ranges', collectMultiFromGrid('price_ranges'));
    }
    else if (idx === 3){
      // Bio/Brand handled by FormData
      fd.set('languages', collectMultiFromGrid('languages'));
    }
    else if (idx === 4){
      fd.set('agree_terms', agreeTerms.checked ? '1' : '0');
      fd.set('digital_signature', sigInput.value.trim());
    }
    return fd;
  }

  function postForm(fd, cb){
    fetch('', {method:'POST', body:fd})
      .then(r=>r.json())
      .then(js=>cb(null, js))
      .catch(e=>cb(e));
  }

  nextBtn.addEventListener('click', ()=>{
    if (!validateStep(current)) return;
    const fd = collectPayload(current);
    showInfo('Saving...');
    postForm(fd, (err,res)=>{
      if (err){ showError('Network error. Please try again.'); return; }
      if (!res.ok){ showError((res.errors||[]).join('; ')); return; }
      submission_id = res.submission_id || submission_id;
      showInfo('Step saved.');
      if (current < last){
        current++;
        showStep(current);
      }
    });
  });

  prevBtn.addEventListener('click', ()=>{
    if (current>0){ current--; showStep(current); }
  });

  submitBtn.addEventListener('click', ()=>{
    if (!validateStep(current)) return;
    const fd = collectPayload(current);
    showInfo('Submitting registration...');
    postForm(fd, (err,res)=>{
      if (err){ showError('Network error. Please try again.'); return; }
      if (!res.ok){ showError((res.errors||[]).join('; ')); return; }
      
      // Success! Update ID and Show Modal
      submission_id = res.submission_id || submission_id;
      
      // Inject the Tracking ID received from server
      if(res.tracking_id) {
          finalId.textContent = res.tracking_id;
      }

      msgBox.classList.add('hidden'); // Hide simple message
      successModal.style.display = 'flex'; // Show popup
      
      nextBtn.disabled = true;
      submitBtn.disabled = true;
    });
  });

  qsa('.step').forEach((s,idx)=>{
    s.addEventListener('click', ()=>{
      if (idx <= current){ current = idx; showStep(current); }
    });
  });

  showStep(current);
})();
</script>
</body>
</html>