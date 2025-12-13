<?php
// index.php - 4-step home builder form
session_start();
require_once __DIR__ . '/db.php';  // provides $pdo

// Allow "Submit another request" to start fresh
if (isset($_GET['new']) && $_GET['new'] === '1') {
    unset($_SESSION['submission_id'], $_SESSION['tracking_code']);
}

function jsonResponse(array $arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

$existing_submission_id = isset($_SESSION['submission_id']) ? (int)$_SESSION['submission_id'] : 0;
$existing_tracking_code = isset($_SESSION['tracking_code']) ? $_SESSION['tracking_code'] : null;

/* ================== AJAX handler: save step ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action']) && $_POST['action'] === 'save_step') {

    $step = isset($_POST['step']) ? (int)$_POST['step'] : 0;
    $submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : $existing_submission_id;

    if (!isset($pdo) || !$pdo) {
        jsonResponse(['ok' => false, 'errors' => ['DB connection unavailable']]);
    }

    $get = function ($k) {
        return isset($_POST[$k]) ? trim((string)$_POST[$k]) : null;
    };

    $errors = [];
    $data   = [];

    // -------- Step 1: Personal --------
    if ($step === 1) {
        $data['first_name'] = $get('first_name');
        $data['last_name']  = $get('last_name');
        $data['phone']      = $get('phone');
        $data['preferred_contact_method'] = $get('preferred_contact_method');
        $data['email']      = $get('email');

        if ($data['first_name'] === '' || $data['last_name'] === '') {
            $errors[] = 'First and last name are required.';
        }
        if (!preg_match('/^\d{10}$/', $data['phone'] ?? '')) {
            $errors[] = 'Phone must be exactly 10 digits.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
    }
    // -------- Step 2: Property --------
    elseif ($step === 2) {
        $own       = strtolower($get('own_land') ?? '');
        $utilities = strtolower($get('utilities') ?? '');

        $data['own_land'] = in_array($own, ['yes', 'no'], true) ? $own : null;
        $data['preferred_location'] = $get('preferred_location');
        $data['plot_size']          = $get('plot_size');
        $data['zoning']             = $get('zoning');
        $data['utilities']          = in_array($utilities, ['yes', 'no'], true) ? $utilities : null;
    }
    // -------- Step 3: Requirements --------
    elseif ($step === 3) {
        $data['home_type']  = $get('home_type');
        $data['floors']     = $get('floors');
        $data['bedrooms']   = ($get('bedrooms') !== '') ? (int)$get('bedrooms') : null;
        $data['bathrooms']  = ($get('bathrooms') !== '') ? (int)$get('bathrooms') : null;
        $data['garage']     = $get('garage');
        $data['estimated_budget'] = $get('estimated_budget');
        $data['preferred_start_date'] = $get('preferred_start_date') ?: null;
        $data['expected_completion_date'] = $get('expected_completion_date') ?: null;
        $data['design_style'] = $get('design_style');
        $data['materials_preference'] = $get('materials_preference');

        if ($data['bedrooms'] === null || $data['bathrooms'] === null) {
            $errors[] = 'Bedrooms and bathrooms are required.';
        }
        if ($data['bedrooms'] !== null && $data['bedrooms'] < 0) {
            $errors[] = 'Bedrooms cannot be negative.';
        }
        if ($data['bathrooms'] !== null && $data['bathrooms'] < 0) {
            $errors[] = 'Bathrooms cannot be negative.';
        }
    }
    // -------- Step 4: Finalize --------
    elseif ($step === 4) {
        $data['service_architectural'] = ($get('service_architectural') === '1') ? 1 : 0;
        $data['service_interior']      = ($get('service_interior') === '1') ? 1 : 0;
        $data['service_landscape']     = ($get('service_landscape') === '1') ? 1 : 0;
        $data['service_permit']        = ($get('service_permit') === '1') ? 1 : 0;
        $data['service_loan']          = ($get('service_loan') === '1') ? 1 : 0;

        $data['additional_notes'] = $get('additional_notes');
        $data['confirm_accuracy'] = ($get('confirm_accuracy') === '1') ? 1 : 0;
        $data['agree_terms']      = ($get('agree_terms') === '1') ? 1 : 0;
    } else {
        $errors[] = 'Invalid step.';
    }

    if (!empty($errors)) {
        jsonResponse(['ok' => false, 'errors' => $errors]);
    }

    try {
        if ($submission_id > 0) {
            // UPDATE existing row
            $set    = [];
            $params = [];

            foreach ($data as $k => $v) {
                $set[] = "`$k` = :$k";
                $params[":$k"] = $v;
            }

            $set[]           = "last_completed_step = GREATEST(last_completed_step, :step)";
            $params[':step'] = $step;
            $params[':id']   = $submission_id;

            $sql  = "UPDATE submissions SET " . implode(', ', $set) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $tracking_code = $existing_tracking_code ?? null;
        } else {
            // INSERT new row
            $cols    = [];
            $holders = [];
            $params  = [];

            foreach ($data as $k => $v) {
                $cols[]     = "`$k`";
                $holders[]  = ":$k";
                $params[":$k"] = $v;
            }

            $cols[]    = "last_completed_step";
            $holders[] = ":last_completed_step";
            $params[':last_completed_step'] = $step;

            // generate tracking code one time at creation
            $tracking_code = 'DH-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $cols[]    = "tracking_code";
            $holders[] = ":tracking_code";
            $params[':tracking_code'] = $tracking_code;

            $sql  = "INSERT INTO submissions (" . implode(',', $cols) .
                    ") VALUES (" . implode(',', $holders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $submission_id = (int)$pdo->lastInsertId();
            $_SESSION['submission_id']  = $submission_id;
            $_SESSION['tracking_code']  = $tracking_code;
        }

        if (!isset($_SESSION['tracking_code'])) {
            $_SESSION['tracking_code'] = $tracking_code ?? null;
        }

        jsonResponse([
            'ok'            => true,
            'submission_id' => $submission_id,
            'step'          => $step,
            'tracking_code' => $_SESSION['tracking_code'] ?? $tracking_code ?? null,
        ]);
    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'errors' => ['DB error: ' . $e->getMessage()]]);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>4-step Progressive Form</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f4f8fb;
  --muted:#6b7280;
  --blue:#2563eb;
  --green:#10b981;
  --orange:#ff6a00;
  --purple:#7c3aed;
  --dark:#020617;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Inter,system-ui,Arial,sans-serif;
  background:linear-gradient(180deg,#f8fbff,var(--bg));
  color:#0f172a;
}
.container{
  max-width:1100px;
  margin:32px auto;
  padding:0 24px;
}

/* top bar with back button + title */
.top-bar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:4px;
}
/* Update this section in your <style> block */
.back-home-btn {
  position: absolute; /* Stick to top left corner */
    top: 20px;
    left: 150px;
    background: #fff;
    border: 1px solid #ddd;
    padding: 10px 20px;
    border-radius: 30px; /* Pill shape */
    cursor: pointer;
    font-weight: 600;
    color: var(--text-dark);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    z-index: 100; /* Ensure it stays on top */
}
.back-home-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #0f172a;
  transform: translateX(-2px); /* Subtle slide effect */
}
/* 1. Add this to center the container itself */
.header {
    text-align: center;
    flex: 1; /* This ensures it takes up the middle space between buttons */
}

/* 2. Your edited lines (kept clean) */
.header h1 { 
    margin: 2px 0 2px; 
    font-size: 20px; 
    font-weight: 700; 
}

.header p { 
    margin: 0; 
    color: var(--muted); 
    font-size: 14px; 
}

/* top progress bar */
.top-progress{
  height:10px;
  background:#e5edff;
  border-radius:999px;
  margin:16px 0 20px;
  overflow:hidden;
}
.top-progress .fill{
  height:100%;
  width:0;
  background:linear-gradient(90deg,var(--dark),#4c1d95);
  transition:width .3s ease;
}

/* steps row */
.steps{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin:24px 40px 20px;
}
.step{
  flex:1;
  text-align:center;
}
/* ... rest of styles are same as earlier ... */

.bubble{
  width:60px;
  height:60px;
  border-radius:50%;
  background:#fff;
  border:2px solid #e2ecff;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  box-shadow:0 10px 30px rgba(15,23,42,0.08);
}
.step .label{
  margin-top:8px;
  color:var(--muted);
  font-size:13px;
}
.step.active .bubble{
  transform:translateY(-10px);
  background:linear-gradient(180deg,var(--blue),#06b6d4);
  color:#fff;
  border-color:transparent;
}
.step.done .bubble{
  background:var(--green);
  color:#fff;
  border-color:transparent;
  transform:translateY(-6px);
}

/* card */
.card{
  background:#fff;
  border-radius:12px;
  margin:18px 40px;
  box-shadow:0 18px 48px rgba(15,23,42,0.08);
  overflow:hidden;
}

/* step header (blue bar) */
.card-header{
  display:flex;
  align-items:center;
  padding:14px 24px;
  background:linear-gradient(90deg,#1f73ff,#1689ff);
  color:#fff;
  border-radius:12px 12px 0 0;
}
.card-header.green{background:linear-gradient(90deg,#16a34a,#22c55e);}
.card-header.orange{background:linear-gradient(90deg,#f97316,#fb923c);}
.card-header.purple{background:linear-gradient(90deg,#7c3aed,#a855f7);}

.card-header__icon{
  width:40px;
  height:40px;
  border-radius:14px;
  background:rgba(255,255,255,0.18);
  display:flex;
  align-items:center;
  justify-content:center;
  margin-right:14px;
}
.card-header__icon-inner{
  font-size:20px;
}
.card-header__text{
  display:flex;
  flex-direction:column;
}
.card-header__title{
  font-size:18px;
  font-weight:600;
  line-height:1.2;
}
.card-header__subtitle{
  font-size:14px;
  margin-top:2px;
  opacity:0.95;
}

/* body */
.card-body{
  padding:26px 36px 30px;
  background:linear-gradient(180deg,#fff,#fbfdff);
}
.form-row{
  display:flex;
  gap:18px;
  margin-bottom:14px;
}
.col{flex:1}
.input label{
  display:block;
  margin-bottom:6px;
  font-weight:600;
  color:#111827;
}
.input input,.input select,.input textarea{
  width:100%;
  padding:11px 13px;
  border-radius:10px;
  border:1px solid #e5edf6;
  background:#fbfdff;
  font-size:14px;
}
.input textarea{min-height:110px;resize:vertical}

/* message boxes */
#msg,#err{
  padding:10px 12px;
  border-radius:8px;
  margin-bottom:12px;
  font-size:13px;
}
#msg{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;}
#err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}

/* radio cards */
.radio-row{
  display:flex;
  gap:14px;
  margin-bottom:14px;
}
.radio-card{
  flex:1;
  padding:12px 14px;
  border-radius:10px;
  border:1px solid #e3edf7;
  background:#fff;
  display:flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  font-size:14px;
}
.radio-card .dot{
  width:18px;
  height:18px;
  border-radius:50%;
  border:2px solid #d4e3f5;
}
.radio-card.selected{
  background:#f0fdf4;
  border-color:#bbf7d0;
}
.radio-card.selected .dot{
  background:#22c55e;
  border-color:#22c55e;
}

/* utilities cards */
.util-row{
  display:flex;
  gap:14px;
  margin:8px 0 12px;
}
.util-card{
  flex:1;
  padding:10px 12px;
  border-radius:10px;
  border:1px solid #e3edf7;
  background:#fff;
  cursor:pointer;
  text-align:center;
  font-size:14px;
}
.util-card.selected{
  background:#f0fdf4;
  border-color:#bbf7d0;
}

/* tip */
.tip{
  padding:10px 12px;
  border-radius:8px;
  background:#ecfdf3;
  border:1px solid #bbf7d0;
  color:#166534;
  margin-top:10px;
  font-size:13px;
}

/* finalize */
.check-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:10px;
  margin-bottom:12px;
  font-size:14px;
}
.file-input{
  border-radius:8px;
  border:1px solid #e5edf6;
  padding:9px 11px;
  background:#fff;
  font-size:13px;
}

/* actions */
.actions{
  display:flex;
  gap:18px;
  justify-content:space-between;
  align-items:center;
  padding:18px 40px 10px;
}
.btn{
  padding:11px 18px;
  border-radius:10px;
  border:1px solid #e5e7eb;
  background:#fff;
  color:#111827;
  font-weight:600;
  cursor:pointer;
  min-width:160px;
}
.btn.primary{
  background:var(--dark);
  color:#fff;
  border-color:var(--dark);
}
.btn[disabled]{opacity:.6;cursor:not-allowed;}

.hidden{display:none !important;}

/* success overlay */
.success-panel{
  position:fixed;
  inset:0;
  background:rgba(15,23,42,0.55);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:999;
}
.success-card{
  width:100%;
  max-width:520px;
  background:#fff;
  border-radius:24px;
  padding:32px 28px 28px;
  box-shadow:0 30px 60px rgba(15,23,42,0.35);
  text-align:center;
}
.success-icon-wrap{
  width:80px;
  height:80px;
  margin:0 auto 18px;
  border-radius:50%;
  background:#dcfce7;
  display:flex;
  align-items:center;
  justify-content:center;
}
.success-icon-inner{
  width:54px;
  height:54px;
  border-radius:50%;
  background:#22c55e;
  color:#fff;
  font-size:30px;
  font-weight:700;
  display:flex;
  align-items:center;
  justify-content:center;
}
.success-title{
  margin:0 0 8px;
  font-size:24px;
  font-weight:700;
}
.success-subtitle{
  margin:0 0 20px;
  color:#6b7280;
  font-size:14px;
}
.success-tracking-card{
  background:#1d4ed8;
  color:#fff;
  border-radius:18px;
  padding:18px 20px 16px;
  margin-bottom:18px;
}
.success-tracking-label{
  font-size:13px;
  margin-bottom:4px;
}
.success-tracking-code{
  font-size:22px;
  font-weight:700;
  letter-spacing:1px;
  margin-bottom:8px;
}
.success-tracking-note{
  font-size:12px;
}
.success-body-text{
  font-size:14px;
  color:#4b5563;
  margin:10px 0 18px;
}
.success-next-steps{
  text-align:left;
  background:#f1f5f9;
  border-radius:16px;
  padding:14px 16px 14px 18px;
  margin-bottom:22px;
}
.success-next-title{
  font-weight:600;
  margin-bottom:6px;
}
.success-next-steps ul{
  margin:0;
  padding-left:18px;
  font-size:14px;
  color:#4b5563;
}
.success-next-steps li{margin-bottom:4px;}
.success-actions{
  display:flex;
  flex-direction:column;
  gap:10px;
}
.success-btn{
  width:100%;
  padding:11px 16px;
  border-radius:999px;
  border:1px solid #e5e7eb;
  background:#fff;
  font-weight:600;
  cursor:pointer;
}
.success-btn.primary{
  background:#020617;
  color:#fff;
  border-color:#020617;
}
.success-btn:hover{filter:brightness(.97);}

/* Terms & Conditions modal */
.modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(15,23,42,0.55);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:998;
}
.modal-card{
  width:100%;
  max-width:640px;
  max-height:80vh;
  background:#fff;
  border-radius:24px;
  padding:24px 24px 20px;
  box-shadow:0 24px 48px rgba(15,23,42,0.35);
  display:flex;
  flex-direction:column;
}
.modal-body{
  overflow-y:auto;
  padding-right:6px;
}
.modal-title{
  font-size:20px;
  font-weight:700;
  margin-bottom:6px;
}
.modal-subtitle{
  font-size:13px;
  color:#6b7280;
  margin-bottom:16px;
}
.modal-body h3{
  font-size:15px;
  margin:14px 0 4px;
}
.modal-body p{
  font-size:14px;
  color:#4b5563;
  margin-bottom:6px;
}
.modal-highlight{
  margin-top:12px;
  padding:10px 12px;
  border-radius:16px;
  background:#f5f3ff;
  color:#4b5563;
  font-size:14px;
}
.modal-footer{
  margin-top:14px;
  display:flex;
  justify-content:flex-end;
}
.modal-close-btn{
  padding:10px 24px;
  border-radius:999px;
  border:none;
  background:#020617;
  color:#fff;
  font-weight:600;
  cursor:pointer;
}

/* responsive */
@media (max-width:900px){
  .steps{margin:18px;}
  .card{margin:18px 14px;}
  .card-body{padding:22px 18px 24px;}
  .form-row{flex-direction:column;}
  .actions{flex-direction:column;align-items:stretch;padding:16px 24px 10px;}
  .success-card{margin:0 16px;padding:26px 18px 22px;}
  .modal-card{margin:0 16px;padding:20px 16px 18px;}
}
</style>
</head>
<body>
  <button class="back-home-btn" type="button" onclick="window.location.href='index.php'">
    <span style="font-size:16px; margin-right:4px;">←</span> Back to Home
</button>
<div class="container">

  <div class="top-bar">
    
    
    <div class="header">
      <h1>Build Your Dream Home</h1>
      <p>Tell us about your vision and we'll make it a reality</p>
    </div>
    <div style="width:120px"></div>
  </div>

  <div class="top-progress" role="progressbar" aria-label="Form Progress">
    <div id="progressFill" class="fill" style="width:25%"></div>
  </div>

  <div class="steps" id="stepsRow">
    <div class="step active" data-step="1">
      <div class="bubble" id="icon-1">1</div>
      <div class="label">Personal Info</div>
    </div>
    <div class="step" data-step="2">
      <div class="bubble" id="icon-2">2</div>
      <div class="label">Property</div>
    </div>
    <div class="step" data-step="3">
      <div class="bubble" id="icon-3">3</div>
      <div class="label">Requirements</div>
    </div>
    <div class="step" data-step="4">
      <div class="bubble" id="icon-4">4</div>
      <div class="label">Finalize</div>
    </div>
  </div>

  <div class="card">
    <div id="cardHeader" class="card-header">
      <div class="card-header__icon">
        <span class="card-header__icon-inner">👤</span>
      </div>
      <div class="card-header__text">
        <div id="cardTitle" class="card-header__title">Personal Information</div>
        <div id="cardSubtitle" class="card-header__subtitle">Let us know how to reach you</div>
      </div>
    </div>

    <div class="card-body">
      <div id="msg" class="hidden"></div>
      <div id="err" class="hidden"></div>

      <form id="multiForm" method="post" enctype="multipart/form-data" novalidate>
        <!-- STEP 1 -->
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
              <label for="phone">Contact Number *</label>
              <input id="phone" name="phone" type="text" placeholder="9876543210">
            </div>
            <div class="col input">
              <label for="preferred_contact_method">Preferred Contact Method</label>
              <select id="preferred_contact_method" name="preferred_contact_method">
                <option value="">Select method</option>
                <option>Phone</option>
                <option>Email</option>
                <option>WhatsApp</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="email">Email Address *</label>
              <input id="email" name="email" type="email">
            </div>
          </div>

          <div class="tip">
            Privacy Note: Your information is secure and will only be used to contact you about your home building project.
          </div>
        </div>

        <!-- STEP 2 -->
        <div class="panel hidden" data-step="2">
          <div style="margin-bottom:8px;font-weight:600;font-size:14px;">Do you already own the land? *</div>
          <div class="radio-row">
            <div class="radio-card" data-value="yes">
              <span class="dot"></span>
              <div>Yes, I own the land</div>
            </div>
            <div class="radio-card" data-value="no">
              <span class="dot"></span>
              <div>No, I need help finding land</div>
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="preferred_location">Preferred Location</label>
              <input id="preferred_location" name="preferred_location" type="text" placeholder="123 Maple Street, City">
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="plot_size">Plot Size</label>
              <input id="plot_size" name="plot_size" type="text" placeholder="e.g. 0.25 acres or 10,000 sq ft">
            </div>
            <div class="col input">
              <label for="zoning">Zoning / Permit Details</label>
              <input id="zoning" name="zoning" type="text" placeholder="If available">
            </div>
          </div>

          <div style="margin-top:6px;font-weight:600;font-size:14px;">Utilities Available</div>
          <div class="util-row">
            <div class="util-card" data-value="yes">Yes</div>
            <div class="util-card" data-value="no">No</div>
          </div>

          <div class="tip">
            Tip: Don't worry if you don't have all this information yet. We can help assess the property during our consultation.
          </div>
        </div>

        <!-- STEP 3 -->
        <div class="panel hidden" data-step="3">
          <div class="form-row">
            <div class="col input">
              <label for="home_type">Type of Home</label>
              <select id="home_type" name="home_type">
                <option value="">Select home type</option>
                <option>Single-Family</option>
                <option>Duplex</option>
                <option>Villa</option>
                <option>Townhouse</option>
              </select>
            </div>
            <div class="col input">
              <label for="floors">Number of Floors</label>
              <select id="floors" name="floors">
                <option value="">Select floors</option>
                <option>1 Floor</option>
                <option>2 Floors</option>
                <option>3 Floors</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="bedrooms">Bedrooms *</label>
              <input id="bedrooms" name="bedrooms" type="number" min="0">
            </div>
            <div class="col input">
              <label for="bathrooms">Bathrooms *</label>
              <input id="bathrooms" name="bathrooms" type="number" min="0">
            </div>
            <div class="col input">
              <label for="garage">Garage</label>
              <select id="garage" name="garage">
                <option value="">Select</option>
                <option>Yes</option>
                <option>No</option>
                <option>Undecided</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="estimated_budget">Estimated Budget</label>
              <select id="estimated_budget" name="estimated_budget">
                <option value="">Select budget range</option>
                <option>Under ₹20L</option>
                <option>₹20L-50L</option>
                <option>Above ₹50L</option>
              </select>
            </div>
            <div class="col input">
              <label for="preferred_start_date">Preferred Start Date</label>
              <input id="preferred_start_date" name="preferred_start_date" type="date">
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="expected_completion_date">Expected Completion</label>
              <input id="expected_completion_date" name="expected_completion_date" type="date">
            </div>
            <div class="col input">
              <label for="design_style">Design Style</label>
              <select id="design_style" name="design_style">
                <option value="">Select style</option>
                <option>Modern</option>
                <option>Traditional</option>
                <option>Contemporary</option>
                <option>Craftsman</option>
                <option>Custom</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="col input">
              <label for="materials_preference">Materials Preference</label>
              <select id="materials_preference" name="materials_preference">
                <option value="">Select materials</option>
                <option>Brick</option>
                <option>Vinyl</option>
                <option>Stone</option>
                <option>Wood</option>
                <option>Mixed Materials</option>
              </select>
            </div>
          </div>

          <div class="tip">
            Remember: These are initial preferences. We'll work together to refine the details during your consultation.
          </div>
        </div>

        <!-- STEP 4 -->
        <div class="panel hidden" data-step="4">
          <div style="margin-bottom:10px;font-weight:600;font-size:14px;">Additional Services Needed</div>
          <div class="check-grid">
            <label><input type="checkbox" name="service_architectural" value="1"> Architectural Design</label>
            <label><input type="checkbox" name="service_interior" value="1"> Interior Design</label>
            <label><input type="checkbox" name="service_landscape" value="1"> Landscape Design</label>
            <label><input type="checkbox" name="service_permit" value="1"> Permit Assistance</label>
            <label><input type="checkbox" name="service_loan" value="1"> Construction Loan Help</label>
          </div>

          <div style="margin-top:8px;font-weight:600;font-size:14px;">Document Uploads (Optional)</div>
          <div style="margin-top:6px" class="file-input">
            <label>Land Ownership Document (optional)
              <input type="file" name="file_land_ownership">
            </label>
          </div>
          <div style="margin-top:6px" class="file-input">
            <label>Site Photos (optional)
              <input type="file" name="file_site_photos" multiple>
            </label>
          </div>
          <div style="margin-top:6px" class="file-input">
            <label>Reference Design / Inspiration (optional)
              <input type="file" name="file_reference_design" multiple>
            </label>
          </div>

          <div style="margin-top:12px;">
            <label style="display:flex; align-items:flex-start; gap:10px;">
              <input type="checkbox" name="confirm_accuracy" value="1" style="margin-top:3px;">
              <span>
                I confirm that the details provided are accurate to the best of my knowledge and
                I understand that this information will be used to prepare an initial consultation
                and estimate for my home building project.
              </span>
            </label>
          </div>

          <div style="margin-top:12px;">
            <label style="display:flex; align-items:flex-start; gap:10px;">
              <input type="checkbox" name="agree_terms" value="1" style="margin-top:3px;">
              <span>
                I agree to the
                <a href="#" id="termsLink" style="color:#7c3aed; font-weight:600; margin-left:4px;">
                  Terms and Conditions
                </a>
              </span>
            </label>
          </div>

          <div class="tip final-step" style="margin-top:16px;">
            Final Step: Review your information and click Submit Request. Our team will contact you within 24–48 hours.
          </div>

        </div>
      </form>
    </div>
  </div>

  <div class="actions">
    <button id="prevBtn" class="btn" style="visibility:hidden">← Previous</button>
    <div>
      <button id="nextBtn" class="btn primary">Next Step →</button>
      <button id="submitBtn" class="btn primary hidden" disabled>Submit Request</button>
    </div>
  </div>

  <!-- Success overlay -->
  <div id="successPanel" class="success-panel hidden">
    <div class="success-card">
      <div class="success-icon-wrap">
        <div class="success-icon-inner">✓</div>
      </div>
      <h2 class="success-title">Request Submitted Successfully!</h2>
      <p class="success-subtitle">
        Thank you for choosing DreamHome Builders for your custom home project!
      </p>
      <div class="success-tracking-card">
        <div class="success-tracking-label">Your Tracking Number</div>
        <div id="trackingNumber" class="success-tracking-code">DH-0000000000</div>
        <div class="success-tracking-note">
          🔑 Save this number to track your request status
        </div>
      </div>
      <p class="success-body-text">
        Our expert construction team will review your requirements and contact you
        within <strong>24–48 hours</strong> to schedule a consultation and site visit.
      </p>
      <div class="success-next-steps">
        <div class="success-next-title">What happens next:</div>
        <ul>
          <li>Initial consultation call with our builder</li>
          <li>Site assessment and feasibility review</li>
          <li>Detailed project estimate and timeline</li>
        </ul>
      </div>
      <div class="success-actions">
        <button id="successHomeBtn" class="success-btn primary">Return to Home</button>
        <button id="successAgainBtn" class="success-btn">Submit Another Request</button>
      </div>
    </div>
  </div>

  <!-- Terms & Conditions Modal -->
  <div id="termsModal" class="modal-overlay hidden">
    <div class="modal-card">
      <div class="modal-body">
        <h2 class="modal-title">Terms and Conditions</h2>
        <p class="modal-subtitle">
          Please read these terms carefully before submitting your home building request.
        </p>

        <h3>1. Purpose of this Form</h3>
        <p>
          This form is designed to collect information about your home building project so that
          our team can understand your needs and prepare an initial consultation and estimate.
        </p>

        <h3>2. Accuracy of Information</h3>
        <p>
          You agree that the information you provide is true and accurate to the best of your knowledge.
          Inaccurate or incomplete information may affect timelines, pricing, and feasibility.
        </p>

        <h3>3. Non-Binding Estimate</h3>
        <p>
          Any estimate or proposal we provide based on this form is for planning purposes only and
          does not create a binding contract. A separate written agreement will be required before
          any construction work begins.
        </p>

        <h3>4. Communication and Follow-Up</h3>
        <p>
          By submitting this form, you consent to being contacted by our team via phone, email,
          or messaging apps using the contact details you provide.
        </p>

        <h3>5. Privacy</h3>
        <p>
          Your personal information will be used solely for the purpose of responding to your request.
          We do not sell your data. Information may be shared only with trusted partners involved
          in planning or executing your project (for example, architects or permit offices).
        </p>

        <h3>6. Document Uploads</h3>
        <p>
          Any documents you upload (such as land ownership papers or reference photos) will be kept
          confidential and used only for project assessment and design purposes.
        </p>

        <h3>7. No Obligation</h3>
        <p>
          Submitting this form does not obligate you to proceed with construction. You are free
          to discontinue discussions at any time before signing a formal contract.
        </p>

        <h3>8. Electronic Acknowledgement</h3>
        <p>
          By checking the “I agree to the Terms and Conditions” checkbox in the form, you indicate
          that you have read, understood, and agree to these terms.
        </p>

        <div class="modal-highlight">
          <strong>Questions?</strong> If you have any questions about these terms, please contact us
          before submitting the form.
        </div>
      </div>
      <div class="modal-footer">
        <button id="termsCloseBtn" class="modal-close-btn">Close</button>
      </div>
    </div>
  </div>

</div> <!-- /.container -->

<script>
(function(){
  const qs  = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  const panels       = qsa('.panel');
  const steps        = qsa('.step');
  const progressFill = qs('#progressFill');
  const prevBtn      = qs('#prevBtn');
  const nextBtn      = qs('#nextBtn');
  const submitBtn    = qs('#submitBtn');
  const cardHeader   = qs('#cardHeader');
  const cardTitle    = qs('#cardTitle');
  const cardSubtitle = qs('#cardSubtitle');
  const msg          = qs('#msg');
  const err          = qs('#err');

  const successPanel     = qs('#successPanel');
  const trackingNumberEl = qs('#trackingNumber');
  const successHomeBtn   = qs('#successHomeBtn');
  const successAgainBtn  = qs('#successAgainBtn');

  // Terms modal elements
  const termsLink    = qs('#termsLink');
  const termsModal   = qs('#termsModal');
  const termsClose   = qs('#termsCloseBtn');

  let current  = 0;
  const last   = panels.length - 1;
  let submission_id = <?php echo $existing_submission_id; ?>;
  let tracking_code  = <?php echo $existing_tracking_code ? json_encode($existing_tracking_code) : 'null'; ?>;

  const form = document.getElementById('multiForm');

  const stepMeta = [
    { title:'Personal Information',  subtitle:'Let us know how to reach you',           color:'blue' },
    { title:'Property Details',     subtitle:'Tell us about your building site',       color:'green' },
    { title:'Project Requirements', subtitle:'Describe your dream home',              color:'orange' },
    { title:'Finalize Your Request',subtitle:'Additional services and final details', color:'purple' }
  ];

  function applyHeader(i){
    cardTitle.textContent    = stepMeta[i].title;
    cardSubtitle.textContent = stepMeta[i].subtitle;
    cardHeader.classList.remove('green','orange','purple');
    if (stepMeta[i].color === 'green')  cardHeader.classList.add('green');
    if (stepMeta[i].color === 'orange') cardHeader.classList.add('orange');
    if (stepMeta[i].color === 'purple') cardHeader.classList.add('purple');
  }

  function updateSubmitState(){
    const agree = form.elements['agree_terms'];
    if (current === last && agree) {
      submitBtn.disabled = !agree.checked;
    } else {
      submitBtn.disabled = false;
    }
  }

  function show(i){
    panels.forEach((p,idx)=> p.classList.toggle('hidden', idx !== i));
    steps.forEach((s,idx)=>{
      s.classList.toggle('active', idx === i);
      s.classList.toggle('done', idx < i);
    });
    progressFill.style.width = Math.round((i)/(last) * 100) + '%';
    prevBtn.style.visibility = (i === 0) ? 'hidden' : 'visible';
    nextBtn.classList.toggle('hidden', i === last);
    submitBtn.classList.toggle('hidden', i !== last);
    applyHeader(i);
    updateSubmitState();
  }

  function showError(text){
    err.textContent = text;
    err.classList.remove('hidden');
    msg.classList.add('hidden');
  }
  function showMsg(text){
    msg.textContent = text;
    msg.classList.remove('hidden');
    err.classList.add('hidden');
  }

  // === Radio handlers for Step 2 ===
  qsa('.radio-card').forEach(card => {
    card.addEventListener('click', () => {
      qsa('.radio-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');

      let h = form.elements['own_land'];
      if (!h) {
        h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'own_land';
        form.appendChild(h);
      }
      h.value = card.getAttribute('data-value').toLowerCase();
    });
  });

  qsa('.util-card').forEach(card => {
    card.addEventListener('click', () => {
      qsa('.util-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');

      let h = form.elements['utilities'];
      if (!h) {
        h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'utilities';
        form.appendChild(h);
      }
      h.value = card.getAttribute('data-value').toLowerCase();
    });
  });

  function validate(i){
    if (i === 0) {
      const fn    = qs('#first_name').value.trim();
      const ln    = qs('#last_name').value.trim();
      const phone = qs('#phone').value.trim();
      const email = qs('#email').value.trim();
      if (!fn || !ln) { showError('First and last name are required'); return false; }
      if (!/^\d{10}$/.test(phone)) { showError('Phone must be exactly 10 digits'); return false; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('Enter a valid email'); return false; }
    }
    if (i === 2) {
      const bd = qs('#bedrooms').value;
      const ba = qs('#bathrooms').value;
      if (!bd) { showError('Number of bedrooms is required'); return false; }
      if (!ba) { showError('Number of bathrooms is required'); return false; }
    }
    if (i === 3) {
      const confirm = form.elements['confirm_accuracy'];
      const agree   = form.elements['agree_terms'];
      if (!confirm || !confirm.checked) {
        showError('Please confirm that the details you provided are accurate.');
        return false;
      }
      if (!agree || !agree.checked) {
        showError('You must agree to the Terms and Conditions before submitting.');
        return false;
      }
    }
    return true;
  }

  function collect(i){
    const fd = new FormData();
    fd.append('action','save_step');
    fd.append('step', i + 1);
    if (submission_id) fd.append('submission_id', submission_id);

    if (i === 0){
      ['first_name','last_name','phone','preferred_contact_method','email'].forEach(k => {
        fd.append(k, form.elements[k] ? form.elements[k].value : '');
      });
    } else if (i === 1){
      fd.append('own_land', form.elements['own_land'] ? form.elements['own_land'].value : '');
      fd.append('preferred_location', form.elements['preferred_location'] ? form.elements['preferred_location'].value : '');
      fd.append('plot_size', form.elements['plot_size'] ? form.elements['plot_size'].value : '');
      fd.append('zoning', form.elements['zoning'] ? form.elements['zoning'].value : '');
      fd.append('utilities', form.elements['utilities'] ? form.elements['utilities'].value : '');
    } else if (i === 2){
      ['home_type','floors','bedrooms','bathrooms','garage','estimated_budget',
       'preferred_start_date','expected_completion_date','design_style','materials_preference']
       .forEach(k => fd.append(k, form.elements[k] ? form.elements[k].value : ''));
    } else if (i === 3){
      ['service_architectural','service_interior','service_landscape',
       'service_permit','service_loan','additional_notes','confirm_accuracy','agree_terms']
       .forEach(k => {
          if (k.startsWith('service_') || k === 'confirm_accuracy' || k === 'agree_terms') {
            fd.append(k, form.elements[k] && form.elements[k].checked ? '1' : '0');
          } else {
            fd.append(k, form.elements[k] ? form.elements[k].value : '');
          }
       });
      // file inputs ignored on server for now
    }
    return fd;
  }

  function post(fd, cb){
    fetch('', { method:'POST', body:fd })
      .then(r => r.json())
      .then(js => cb(null, js))
      .catch(e => cb(e));
  }

  function showSuccessPanel(tracking){
    tracking_code = tracking || tracking_code || ('DH-' + (submission_id || '0000000000'));
    trackingNumberEl.textContent = tracking_code;
    successPanel.classList.remove('hidden');
  }

  nextBtn.addEventListener('click', () => {
    if (!validate(current)) return;
    const fd = collect(current);
    showMsg('Saving...');
    post(fd, (errAjax, res) => {
      if (errAjax) { showError('Network error, please try again.'); return; }
      if (!res.ok) { showError((res.errors || []).join('; ')); return; }

      submission_id = res.submission_id || submission_id;
      if (res.tracking_code) tracking_code = res.tracking_code;

      const bubble = qs('#icon-' + (current + 1));
      if (bubble) { bubble.innerHTML = '✓'; bubble.parentElement.classList.add('done'); }

      if (current < last) {
        current++;
        show(current);
        showMsg('Step saved.');
      } else {
        showMsg('All steps complete.');
      }
    });
  });

  prevBtn.addEventListener('click', () => {
    if (current > 0) { current--; show(current); }
  });

  submitBtn.addEventListener('click', () => {
    if (!validate(current)) return;
    const fd = collect(current);
    showMsg('Submitting...');
    post(fd, (errAjax, res) => {
      if (errAjax) { showError('Network error, please try again.'); return; }
      if (!res.ok) { showError((res.errors || []).join('; ')); return; }

      submission_id = res.submission_id || submission_id;
      if (res.tracking_code) tracking_code = res.tracking_code;

      const bubble = qs('#icon-' + (current + 1));
      if (bubble) { bubble.innerHTML = '✓'; bubble.parentElement.classList.add('done'); }

      nextBtn.disabled   = true;
      submitBtn.disabled = true;

      showSuccessPanel(tracking_code);
    });
  });

  // Success actions
  successHomeBtn.addEventListener('click', () => {
    window.location.href = '/';
  });
  successAgainBtn.addEventListener('click', () => {
    window.location.href = '?new=1';
  });

  // allow clicking previous steps (already completed)
  qsa('.step').forEach((s, idx) => {
    s.addEventListener('click', () => {
      if (idx <= current) {
        current = idx;
        show(current);
      }
    });
  });

  // === Terms & Conditions modal logic ===
  if (termsLink && termsModal && termsClose) {
    termsLink.addEventListener('click', (e) => {
      e.preventDefault();
      termsModal.classList.remove('hidden');
    });
    termsClose.addEventListener('click', () => {
      termsModal.classList.add('hidden');
    });
    // click outside card closes modal
    termsModal.addEventListener('click', (e) => {
      if (e.target === termsModal) {
        termsModal.classList.add('hidden');
      }
    });
  }

  // enable/disable submit when checkbox changes
  const agreeCheckbox = form.elements['agree_terms'];
  if (agreeCheckbox) {
    agreeCheckbox.addEventListener('change', updateSubmitState);
  }

  show(current);
})();
</script>
</body>
</html>
