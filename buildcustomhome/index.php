<?php
// index.php - 4-step home builder form
session_start();
require_once __DIR__ . '/db.php';  // provides $pdo

// Allow "Submit another request" to start fresh
if (isset($_GET['new']) && $_GET['new'] === '1') {
    unset($_SESSION['submission_id'], $_SESSION['tracking_code']);
    header("Location: index.php");
    exit;
}

function jsonResponse(array $arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

$existing_submission_id = isset($_SESSION['submission_id']) ? (int)$_SESSION['submission_id'] : 0;
$existing_tracking_code = isset($_SESSION['tracking_code']) ? $_SESSION['tracking_code'] : null;

/* ================== AJAX handler: save step ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_step') {

    $step = isset($_POST['step']) ? (int)$_POST['step'] : 0;
    $submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : $existing_submission_id;

    if (!isset($pdo) || !$pdo) {
        // jsonResponse(['ok' => false, 'errors' => ['DB connection unavailable']]);
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

        if ($data['first_name'] === '' || $data['last_name'] === '') $errors[] = 'First and last name are required.';
        if (!preg_match('/^\d{10}$/', $data['phone'] ?? '')) $errors[] = 'Phone must be exactly 10 digits.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    }
    // -------- Step 2: Property --------
    elseif ($step === 2) {
        $own       = strtolower($get('own_land') ?? '');
        $utilities = strtolower($get('utilities') ?? '');

        $data['own_land'] = in_array($own, ['yes', 'no'], true) ? $own : null;
        
        // Save either Land Address OR Preferred Location based on selection
        $data['preferred_location'] = $get('preferred_location'); 
        $data['land_address']       = $get('land_address'); 

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
        $data['garage_spaces'] = ($data['garage'] === 'Yes' && $get('garage_spaces') !== '') ? (int)$get('garage_spaces') : null;

        $data['estimated_budget'] = $get('estimated_budget');
        $data['preferred_start_date'] = $get('preferred_start_date') ?: null;
        $data['expected_completion_date'] = $get('expected_completion_date') ?: null;
        $data['design_style'] = $get('design_style');
        $data['materials_preference'] = $get('materials_preference');

        if ($data['bedrooms'] === null || $data['bathrooms'] === null) $errors[] = 'Bedrooms and bathrooms are required.';
    }
    // -------- Step 4: Finalize & UPLOADS --------
    elseif ($step === 4) {
        $data['service_architectural'] = ($get('service_architectural') === '1') ? 1 : 0;
        $data['service_interior']      = ($get('service_interior') === '1') ? 1 : 0;
        $data['service_landscape']     = ($get('service_landscape') === '1') ? 1 : 0;
        $data['service_permit']        = ($get('service_permit') === '1') ? 1 : 0;
        $data['service_loan']          = ($get('service_loan') === '1') ? 1 : 0;

        $data['additional_notes'] = $get('additional_notes');
        $data['confirm_accuracy'] = ($get('confirm_accuracy') === '1') ? 1 : 0;
        $data['agree_terms']      = ($get('agree_terms') === '1') ? 1 : 0;
        $data['digital_signature'] = $get('digital_signature');

        if ($data['agree_terms'] !== 1) $errors[] = 'You must agree to the Terms and Conditions.';
        if (empty($data['digital_signature'])) $errors[] = 'Please provide your electronic signature.';

        // --- FILE UPLOAD LOGIC (builduploads) ---
        $uploadDir = 'builduploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // 1. Land Ownership
        if (isset($_FILES['file_land_ownership']) && $_FILES['file_land_ownership']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['file_land_ownership']['name'], PATHINFO_EXTENSION);
            $newName = 'land_' . time() . '_' . uniqid() . '.' . $ext;
            if(move_uploaded_file($_FILES['file_land_ownership']['tmp_name'], $uploadDir . $newName)) {
                $data['file_land_ownership'] = $newName;
            }
        }

        // 2. Site Photos (Multiple)
        if (isset($_FILES['file_site_photos'])) {
            $uploaded = [];
            foreach ($_FILES['file_site_photos']['name'] as $key => $val) {
                if ($_FILES['file_site_photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['file_site_photos']['name'][$key], PATHINFO_EXTENSION);
                    $newName = 'site_' . time() . '_' . $key . '_' . uniqid() . '.' . $ext;
                    if(move_uploaded_file($_FILES['file_site_photos']['tmp_name'][$key], $uploadDir . $newName)) {
                        $uploaded[] = $newName;
                    }
                }
            }
            if(!empty($uploaded)) $data['file_site_photos'] = implode(',', $uploaded);
        }

        // 3. Reference Design (Multiple)
        if (isset($_FILES['file_reference_design'])) {
            $uploaded = [];
            foreach ($_FILES['file_reference_design']['name'] as $key => $val) {
                if ($_FILES['file_reference_design']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['file_reference_design']['name'][$key], PATHINFO_EXTENSION);
                    $newName = 'ref_' . time() . '_' . $key . '_' . uniqid() . '.' . $ext;
                    if(move_uploaded_file($_FILES['file_reference_design']['tmp_name'][$key], $uploadDir . $newName)) {
                        $uploaded[] = $newName;
                    }
                }
            }
            if(!empty($uploaded)) $data['file_reference_design'] = implode(',', $uploaded);
        }
    } else {
        $errors[] = 'Invalid step.';
    }

    if (!empty($errors)) {
        jsonResponse(['ok' => false, 'errors' => $errors]);
    }

    try {
        if (isset($pdo)) {
            if ($submission_id > 0) {
                // UPDATE
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
                // INSERT
                $cols    = [];
                $holders = [];
                $params  = [];
                foreach ($data as $k => $v) {
                    $cols[]        = "`$k`";
                    $holders[]     = ":$k";
                    $params[":$k"] = $v;
                }
                $cols[]                             = "last_completed_step";
                $holders[]                          = ":last_completed_step";
                $params[':last_completed_step'] = $step;

                $tracking_code = 'DH-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
                $cols[]        = "tracking_code";
                $holders[]     = ":tracking_code";
                $params[':tracking_code'] = $tracking_code;

                $sql  = "INSERT INTO submissions (" . implode(',', $cols) . ") VALUES (" . implode(',', $holders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                $submission_id = (int)$pdo->lastInsertId();
                $_SESSION['submission_id']  = $submission_id;
                $_SESSION['tracking_code']  = $tracking_code;
            }
        } else {
            // Mock ID for demo if no DB
            if ($submission_id == 0) $submission_id = 999;
            if (!$existing_tracking_code) $tracking_code = 'DH-DEMO123';
        }
        
        // UNSET session on final step
        if ($step === 4) {
            unset($_SESSION['submission_id']);
            unset($_SESSION['tracking_code']);
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
<title>Build Custom Home</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
  --bg:#f4f8fb; --muted:#6b7280; --blue:#2563eb; --green:#10b981; --orange:#ff6a00; --purple:#7c3aed; --dark:#020617;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Inter,system-ui,Arial,sans-serif; background:linear-gradient(180deg,#f8fbff,var(--bg)); color:#0f172a;
}
.container{ max-width:1100px; margin:32px auto; padding:0 24px; }

/* Top Bar */
.top-bar{ display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; }
.back-home-btn {
  position: absolute; top: 10px; left: 200px; background: #fff; border: 1px solid #ddd;
  padding: 10px 20px; border-radius: 30px; cursor: pointer; font-weight: 600; color: var(--text-dark);
  display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.3s ease; z-index: 100;
}
.back-home-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; transform: translateX(-2px); }
.header { text-align: center; flex: 1; }
.header h1 { margin: 2px 0 2px; font-size: 20px; font-weight: 700; }
.header p { margin: 0; color: var(--muted); font-size: 14px; }

/* Progress Bar */
.top-progress{ height:10px; background:#e5edff; border-radius:999px; margin:16px 0 20px; overflow:hidden; }
.top-progress .fill{ height:100%; width:0; background:linear-gradient(90deg,var(--dark),#4c1d95); transition:width .3s ease; }

/* Steps */
.steps{ display:flex; justify-content:space-between; align-items:center; margin:24px 40px 20px; }
.step{ flex:1; text-align:center; }
.bubble{
  width:60px; height:60px; border-radius:50%; background:#fff; border:2px solid #e2ecff;
  display:inline-flex; align-items:center; justify-content:center; font-weight:700;
  box-shadow:0 10px 30px rgba(15,23,42,0.08); transition:all 0.3s;
}
.step .label{ margin-top:8px; color:var(--muted); font-size:13px; }
.step.active .bubble{ transform:translateY(-10px); background:linear-gradient(180deg,var(--blue),#06b6d4); color:#fff; border-color:transparent; }
.step.done .bubble{ background:var(--green); color:#fff; border-color:transparent; transform:translateY(-6px); }

/* Card */
.card{ background:#fff; border-radius:12px; margin:18px 40px; box-shadow:0 18px 48px rgba(15,23,42,0.08); overflow:hidden; }
.card-header{ display:flex; align-items:center; padding:14px 24px; background:linear-gradient(90deg,#1f73ff,#1689ff); color:#fff; border-radius:12px 12px 0 0; }
.card-header.green{background:linear-gradient(90deg,#16a34a,#22c55e);}
.card-header.orange{background:linear-gradient(90deg,#f97316,#fb923c);}
.card-header.purple{background:linear-gradient(90deg,#7c3aed,#a855f7);}
.card-header__icon{ width:40px; height:40px; border-radius:14px; background:rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; margin-right:14px; }
.card-header__icon-inner{ font-size:20px; }
.card-header__text{ display:flex; flex-direction:column; }
.card-header__title{ font-size:18px; font-weight:600; line-height:1.2; }
.card-header__subtitle{ font-size:14px; margin-top:2px; opacity:0.95; }

/* Body */
.card-body{ padding:26px 36px 30px; background:linear-gradient(180deg,#fff,#fbfdff); }
.form-row{ display:flex; gap:18px; margin-bottom:14px; }
.col{flex:1}
.input label{ display:block; margin-bottom:6px; font-weight:600; color:#111827; }
.input input,.input select,.input textarea{ width:100%; padding:11px 13px; border-radius:10px; border:1px solid #e5edf6; background:#fbfdff; font-size:14px; }
.input textarea{min-height:110px;resize:vertical}

/* Messages */
#msg,#err{ padding:10px 12px; border-radius:8px; margin-bottom:12px; font-size:13px; }
#msg{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;}
#err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}

/* Radio Cards */
.radio-row{ display:flex; gap:14px; margin-bottom:14px; }
.radio-card{
  flex:1; padding:12px 14px; border-radius:10px; border:1px solid #e3edf7; background:#fff;
  display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px;
}
.radio-card .dot{ width:18px; height:18px; border-radius:50%; border:2px solid #d4e3f5; }
.radio-card.selected{ background:#f0fdf4; border-color:#bbf7d0; }
.radio-card.selected .dot{ background:#22c55e; border-color:#22c55e; }

/* Utilities */
.util-row{ display:flex; gap:14px; margin:8px 0 12px; }
.util-card{ flex:1; padding:10px 12px; border-radius:10px; border:1px solid #e3edf7; background:#fff; cursor:pointer; text-align:center; font-size:14px; }
.util-card.selected{ background:#f0fdf4; border-color:#bbf7d0; }

/* Tip */
.tip{ padding:10px 12px; border-radius:8px; background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; margin-top:10px; font-size:13px; }

/* Step 4: Specific Styles */
.service-option-row {
    background: #fff; border: 1px solid #e3edf7; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px;
}
.service-option-row label { margin: 0; font-size: 14px; font-weight: 500; color: #1f2937; cursor: pointer; flex: 1; }
.service-option-row input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }

.clean-file-input {
    background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; width: 100%;
}

/* Signature Section */
.signature-box { display: none; margin-top: 15px; padding: 15px; border-radius: 8px; background: #f5f3ff; border: 1px solid #d8b4fe; }
.sig-label { display: block; font-weight: 600; color: #6b21a8; margin-bottom: 8px; font-size: 14px; }
.sig-input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #d8b4fe; font-size: 16px; font-family: cursive; color: #4c1d95; }

/* Actions */
.actions{ display:flex; gap:18px; justify-content:space-between; align-items:center; padding:18px 40px 10px; }
.btn{
  padding:11px 18px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; color:#111827; font-weight:600; cursor:pointer; min-width:160px;
}
.btn.primary{ background:var(--dark); color:#fff; border-color:var(--dark); }
.btn[disabled]{opacity:.6;cursor:not-allowed;}
.hidden{display:none !important;}

/* Success Overlay */
.success-panel{ position:fixed; inset:0; background:rgba(15,23,42,0.55); display:flex; align-items:center; justify-content:center; z-index:999; }
.success-card{ width:100%; max-width:520px; background:#fff; border-radius:24px; padding:32px 28px 28px; box-shadow:0 30px 60px rgba(15,23,42,0.35); text-align:center; }
.success-icon-wrap{ width:80px; height:80px; margin:0 auto 18px; border-radius:50%; background:#dcfce7; display:flex; align-items:center; justify-content:center; }
.success-icon-inner{ width:54px; height:54px; border-radius:50%; background:#22c55e; color:#fff; font-size:30px; font-weight:700; display:flex; align-items:center; justify-content:center; }
.success-title{ margin:0 0 8px; font-size:24px; font-weight:700; }
.success-subtitle{ margin:0 0 20px; color:#6b7280; font-size:14px; }
.success-tracking-card{ background:#1d4ed8; color:#fff; border-radius:18px; padding:18px 20px 16px; margin-bottom:18px; }
.success-tracking-code{ font-size:22px; font-weight:700; letter-spacing:1px; margin-bottom:8px; }
.success-tracking-note{ font-size:12px; }
.success-body-text{ font-size:14px; color:#4b5563; margin:10px 0 18px; }
.success-next-steps{ text-align:left; background:#f1f5f9; border-radius:16px; padding:14px 16px 14px 18px; margin-bottom:22px; }
.success-next-title{ font-weight:600; margin-bottom:6px; }
.success-next-steps ul{ margin:0; padding-left:18px; font-size:14px; color:#4b5563; }
.success-next-steps li{margin-bottom:4px;}
.success-actions{ display:flex; flex-direction:column; gap:10px; }
.success-btn{ width:100%; padding:11px 16px; border-radius:999px; border:1px solid #e5e7eb; background:#fff; font-weight:600; cursor:pointer; }
.success-btn.primary{ background:#020617; color:#fff; border-color:#020617; }
.success-btn:hover{filter:brightness(.97);}

/* Modal */
.modal-overlay{ position:fixed; inset:0; background:rgba(15,23,42,0.55); display:flex; align-items:center; justify-content:center; z-index:998; }
.modal-card{ width:100%; max-width:640px; max-height:80vh; background:#fff; border-radius:24px; padding:24px 24px 20px; box-shadow:0 24px 48px rgba(15,23,42,0.35); display:flex; flex-direction:column; }
.modal-body{ overflow-y:auto; padding-right:6px; }
.modal-close-btn{ padding:10px 24px; border-radius:999px; border:none; background:#020617; color:#fff; font-weight:600; cursor:pointer; }

@media (max-width:900px){ .steps{margin:18px;} .card{margin:18px 14px;} .form-row{flex-direction:column;} .actions{flex-direction:column;align-items:stretch;padding:16px 24px 10px;} }
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

  <div class="top-progress" role="progressbar"><div id="progressFill" class="fill" style="width:25%"></div></div>

  <div class="steps" id="stepsRow">
    <div class="step active" data-step="1"><div class="bubble" id="icon-1">1</div><div class="label">Personal Info</div></div>
    <div class="step" data-step="2"><div class="bubble" id="icon-2">2</div><div class="label">Property</div></div>
    <div class="step" data-step="3"><div class="bubble" id="icon-3">3</div><div class="label">Requirements</div></div>
    <div class="step" data-step="4"><div class="bubble" id="icon-4">4</div><div class="label">Finalize</div></div>
  </div>

  <div class="card">
    <div id="cardHeader" class="card-header">
      <div class="card-header__icon"><span class="card-header__icon-inner">👤</span></div>
      <div class="card-header__text"><div id="cardTitle" class="card-header__title">Personal Information</div><div id="cardSubtitle" class="card-header__subtitle">Let us know how to reach you</div></div>
    </div>

    <div class="card-body">
      <div id="msg" class="hidden"></div>
      <div id="err" class="hidden"></div>

      <form id="multiForm" method="post" enctype="multipart/form-data" novalidate>
        
        <div class="panel" data-step="1">
          <div class="form-row">
            <div class="col input"><label>First Name *</label><input id="first_name" name="first_name" type="text"></div>
            <div class="col input"><label>Last Name *</label><input id="last_name" name="last_name" type="text"></div>
          </div>
          <div class="form-row">
            <div class="col input"><label>Contact Number *</label><input id="phone" name="phone" type="text" placeholder="Enter Phone Number"></div>
            <div class="col input">
              <label>Preferred Contact Method</label>
              <select id="preferred_contact_method" name="preferred_contact_method">
                <option value="">Select method</option><option>Phone</option><option>Email</option><option>WhatsApp</option>
              </select>
            </div>
          </div>
          <div class="form-row"><div class="col input"><label>Email Address *</label><input id="email" name="email" type="email"></div></div>
          <div class="tip">Privacy Note: Your information is secure and will only be used to contact you about your home building project.</div>
        </div>

        <div class="panel hidden" data-step="2">
          <div style="margin-bottom:8px;font-weight:600;font-size:14px;">Do you already own the land? *</div>
          <div class="radio-row">
            <div class="radio-card" id="btnOwnYes" data-value="yes"><span class="dot"></span><div>Yes, I own the land</div></div>
            <div class="radio-card" id="btnOwnNo" data-value="no"><span class="dot"></span><div>No, I need help finding land</div></div>
          </div>
          <input type="hidden" name="own_land" id="own_land">

          <div class="form-row hidden" id="fieldLandAddress">
            <div class="col input">
              <label>Land Address / Location *</label>
              <input name="land_address" type="text" placeholder="Enter the specific address or location">
            </div>
          </div>

          <div class="form-row hidden" id="fieldPreferredLoc">
            <div class="col input">
              <label>Preferred Location *</label>
              <input name="preferred_location" type="text" placeholder="Where would you like to build? (City, Zip)">
            </div>
          </div>
          <div class="form-row">
            <div class="col input"><label>Plot Size</label><input id="plot_size" name="plot_size" type="text" placeholder="e.g. 0.25 acres or 10,000 sq ft"></div>
            <div class="col input"><label>Zoning / Permit Details</label><input id="zoning" name="zoning" type="text" placeholder="If available"></div>
          </div>
          <div style="margin-top:6px;font-weight:600;font-size:14px;">Utilities Available</div>
          <div class="util-row">
            <div class="util-card" data-value="yes">Yes</div>
            <div class="util-card" data-value="no">No</div>
          </div>
          <input type="hidden" name="utilities" id="utilities">

          <div class="tip">Tip: Don't worry if you don't have all this information yet. We can help assess the property during our consultation.</div>
        </div>

        <div class="panel hidden" data-step="3">
          <div class="form-row">
            <div class="col input"><label>Type of Home</label><select id="home_type" name="home_type"><option value="">Select home type</option><option>Single-Family</option><option>Duplex</option><option>Villa</option><option>Townhouse</option></select></div>
            <div class="col input"><label>Number of Floors</label><select id="floors" name="floors"><option value="">Select floors</option><option>1 Floor</option><option>2 Floors</option><option>3 Floors</option></select></div>
          </div>
          <div class="form-row">
            <div class="col input"><label>Bedrooms *</label><input id="bedrooms" name="bedrooms" type="number" min="0"></div>
            <div class="col input"><label>Bathrooms *</label><input id="bathrooms" name="bathrooms" type="number" min="0"></div>
            <div class="col input"><label>Garage</label>
                <select id="garage" name="garage">
                    <option value="">Select</option>
                    <option>Yes</option>
                    <option>No</option>
                    <option>Undecided</option>
                </select>
            </div>
            <div class="col input hidden" id="fieldGarageSpaces">
                <label>How many garage spaces?</label>
                <input type="number" id="garage_spaces" name="garage_spaces" min="1" max="10" placeholder="e.g. 2">
            </div>
          </div>
          <div class="form-row">
            <div class="col input"><label>Estimated Budget</label><select id="estimated_budget" name="estimated_budget"><option value="">Select budget range</option><option>Under $300,000</option><option>$300,000-$400,000</option><option>$400,000-$600,000</option><option>$600,000-$800,000</option><option>Over $800,000</option></select></div>
            <div class="col input"><label>Preferred Start Date</label><input id="preferred_start_date" name="preferred_start_date" type="date"></div>
          </div>
          <div class="form-row">
            <div class="col input"><label>Expected Completion</label><input id="expected_completion_date" name="expected_completion_date" type="date"></div>
            <div class="col input"><label>Design Style</label><select id="design_style" name="design_style"><option value="">Select style</option><option>Modern</option><option>Traditional</option><option>Contemporary</option><option>Craftsman</option><option>Custom</option></select></div>
          </div>
          <div class="form-row"><div class="col input"><label>Materials Preference</label><select id="materials_preference" name="materials_preference"><option value="">Select materials</option><option>Brick</option><option>Vinyl</option><option>Stone</option><option>Wood</option><option>Mixed Materials</option></select></div></div>
          <div class="tip">Remember: These are initial preferences. We'll work together to refine the details during your consultation.</div>
        </div>

        <div class="panel hidden" data-step="4">
          <div style="margin-bottom:10px;font-weight:600;font-size:14px;color:#4b5563;">Additional Services Needed</div>
          
          <div class="service-option-row">
            <input type="checkbox" name="service_architectural" id="svc_arch" value="1">
            <label for="svc_arch">Architectural Design</label>
          </div>
          <div class="service-option-row">
            <input type="checkbox" name="service_interior" id="svc_int" value="1">
            <label for="svc_int">Interior Design</label>
          </div>
          <div class="service-option-row">
            <input type="checkbox" name="service_landscape" id="svc_land" value="1">
            <label for="svc_land">Landscape Design</label>
          </div>
          <div class="service-option-row">
            <input type="checkbox" name="service_permit" id="svc_perm" value="1">
            <label for="svc_perm">Permit Assistance</label>
          </div>
          <div class="service-option-row">
            <input type="checkbox" name="service_loan" id="svc_loan" value="1">
            <label for="svc_loan">Construction Loan Help</label>
          </div>

          <div style="margin-top:24px;font-weight:600;font-size:14px;color:#4b5563;margin-bottom:12px;">Document Uploads (Optional)</div>
          
          <div style="margin-bottom:15px;">
              <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Land Ownership Document</label>
              <input type="file" name="file_land_ownership" class="clean-file-input" accept=".pdf,.jpg,.jpeg,.png">
          </div>

          <div style="margin-bottom:15px;">
              <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Site Photos</label>
              <input type="file" name="file_site_photos[]" class="clean-file-input" multiple accept="image/*">
          </div>

          <div style="margin-bottom:20px;">
              <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Reference Design / Inspiration</label>
              <input type="file" name="file_reference_design[]" class="clean-file-input" multiple accept="image/*,.pdf">
          </div>

          <div style="margin-bottom:20px;">
              <label style="display:block;margin-bottom:6px;font-size:14px;font-weight:600;color:#4b5563;">Additional Notes</label>
              <textarea name="additional_notes" style="width:100%;padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#f3f4f6;min-height:100px;font-family:inherit;font-size:14px;" placeholder="Please describe any specific features, requirements, or questions you have about your home building project..."></textarea>
          </div>

          <div style="margin-top:20px;">
            <label style="display:flex; align-items:flex-start; gap:10px;">
              <input type="checkbox" name="confirm_accuracy" value="1" style="margin-top:3px;">
              <span style="font-size:14px;color:#374151;">I confirm that the details provided are accurate to the best of my knowledge.</span>
            </label>
          </div>

          <div style="margin-top:12px;">
            <label style="display:flex; align-items:flex-start; gap:10px;">
              <input type="checkbox" name="agree_terms" value="1" style="margin-top:3px;">
              <span style="font-size:14px;color:#374151;">I agree to the <a href="#" id="termsLink" style="color:#7c3aed; font-weight:600; margin-left:4px;">Terms and Conditions</a></span>
            </label>
          </div>

          <div id="signature-section" class="signature-box">
             <label class="sig-label">🖊️ Your Signature *</label>
             <input type="text" id="digital_signature" name="digital_signature" class="sig-input" placeholder="Type your full name as signature">
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

  <div id="successPanel" class="success-panel hidden">
    <div class="success-card">
      <div class="success-icon-wrap"><div class="success-icon-inner">✓</div></div>
      <h2 class="success-title">Request Submitted Successfully!</h2>
      <p class="success-subtitle">Thank you for choosing DreamHome Builders for your custom home project!</p>
      
      <div class="success-tracking-card">
        <div class="success-tracking-label">Your Tracking Number</div>
        <div id="trackingNumber" class="success-tracking-code">DH-0000000000</div>
        <div class="success-tracking-note">💡 Save this number to track your request status</div>
      </div>
      
      <p class="success-body-text">
        Our expert construction team will review your requirements and contact you within 
        <strong>24–48 hours</strong> to schedule a consultation and site visit.
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
  
  <div id="termsModal" class="modal-overlay hidden">
      <div class="modal-card">
          <div class="modal-body">
            <h2 class="modal-title">Terms & Conditions</h2>
            <p style="margin-bottom:15px; color:#6b7280; font-size:14px;">Please read these terms carefully before submitting your home building request.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">1. Purpose of this Form</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">This form is designed to collect information about your home building project so that our team can understand your needs and prepare an initial consultation and estimate.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">2. Accuracy of Information</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">You agree that the information you provide is true and accurate to the best of your knowledge. Inaccurate or incomplete information may affect timelines, pricing, and feasibility.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">3. Non-Binding Estimate</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">Any estimate or proposal we provide based on this form is for planning purposes only and does not create a binding contract. A separate written agreement will be required before any construction work begins.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">4. Communication and Follow-Up</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">By submitting this form, you consent to being contacted by our team via phone, email, or messaging apps using the contact details you provide.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">5. Privacy</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">Your personal information will be used solely for the purpose of responding to your request. We do not sell your data. Information may be shared only with trusted partners involved in planning or executing your project (for example, architects or permit offices).</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">6. Document Uploads</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">Any documents you upload (such as land ownership papers or reference photos) will be kept confidential and used only for project assessment and design purposes.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">7. No Obligation</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">Submitting this form does not obligate you to proceed with construction. You are free to discontinue discussions at any time before signing a formal contract.</p>

            <h3 style="font-size:15px; font-weight:700; margin:14px 0 4px;">8. Electronic Acknowledgement</h3>
            <p style="font-size:14px; color:#4b5563; margin-bottom:6px;">By checking the “I agree to the Terms and Conditions” checkbox in the form, you indicate that you have read, understood, and agree to these terms.</p>

            <div style="margin-top:12px; padding:10px 12px; border-radius:16px; background:#f5f3ff; color:#4b5563; font-size:14px;">
              <strong>Questions?</strong> If you have any questions about these terms, please contact us before submitting the form.
            </div>
         </div>
         <div class="modal-footer"><button id="termsCloseBtn" class="modal-close-btn">Close</button></div>
     </div>
  </div>

</div>

<script>
(function(){
  const qs = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  // Logic Elements
  const panels = qsa('.panel');
  const steps = qsa('.step');
  const progressFill = qs('#progressFill');
  const prevBtn = qs('#prevBtn');
  const nextBtn = qs('#nextBtn');
  const submitBtn = qs('#submitBtn');
  const form = qs('#multiForm');

  // Step 2 Logic Elements
  const btnOwnYes = qs('#btnOwnYes');
  const btnOwnNo = qs('#btnOwnNo');
  const fieldLandAddress = qs('#fieldLandAddress');
  const fieldPreferredLoc = qs('#fieldPreferredLoc');
  const inputOwnLand = qs('#own_land');
  const inputUtilities = qs('#utilities'); // Hidden input for utilities

  // Step 3 Logic Elements (Garage)
  const inputGarage = qs('#garage');
  const fieldGarageSpaces = qs('#fieldGarageSpaces');
  const inputGarageSpaces = qs('#garage_spaces');

  // Signature Elements
  const termsCheck = qs('input[name="agree_terms"]');
  const sigSection = qs('#signature-section');
  const sigInput = qs('#digital_signature');

  // Success Logic
  const successPanel = qs('#successPanel');
  const successHomeBtn = qs('#successHomeBtn');
  const successAgainBtn = qs('#successAgainBtn');

  // Terms Modal
  const termsLink = qs('#termsLink');
  const termsModal = qs('#termsModal');
  const termsClose = qs('#termsCloseBtn');

  let current = 0;
  const last = panels.length - 1;
  let submission_id = <?php echo $existing_submission_id; ?>;

  const stepMeta = [
    { title:'Personal Information', subtitle:'Let us know how to reach you', color:'blue' },
    { title:'Property Details', subtitle:'Tell us about your building site', color:'green' },
    { title:'Project Requirements', subtitle:'Describe your dream home', color:'orange' },
    { title:'Finalize Your Request',subtitle:'Additional services and final details', color:'purple' }
  ];

  function applyHeader(i){
    qs('#cardTitle').textContent = stepMeta[i].title;
    qs('#cardSubtitle').textContent = stepMeta[i].subtitle;
    qs('#cardHeader').className = 'card-header ' + stepMeta[i].color;
  }

  function show(i){
    // --- CLEAR ERRORS ON STEP CHANGE ---
    qs('#err').classList.add('hidden');
    qs('#msg').classList.add('hidden');

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
  }

  function showError(text){
    qs('#err').textContent = text;
    qs('#err').classList.remove('hidden');
    qs('#msg').classList.add('hidden');
  }

  // --- Step 2 Logic: Land Ownership ---
  function toggleLandFields(val) {
      inputOwnLand.value = val;
      btnOwnYes.classList.toggle('selected', val === 'yes');
      btnOwnNo.classList.toggle('selected', val === 'no');

      if(val === 'yes') {
          fieldLandAddress.classList.remove('hidden');
          fieldPreferredLoc.classList.add('hidden');
      } else {
          fieldLandAddress.classList.add('hidden');
          fieldPreferredLoc.classList.remove('hidden');
      }
  }
  btnOwnYes.addEventListener('click', () => toggleLandFields('yes'));
  btnOwnNo.addEventListener('click', () => toggleLandFields('no'));

  // --- Step 2 Logic: Utilities ---
  qsa('.util-card').forEach(card => {
    card.addEventListener('click', () => {
        // Deselect all
        qsa('.util-card').forEach(c => c.classList.remove('selected'));
        // Select clicked
        card.classList.add('selected');
        // Update hidden input
        inputUtilities.value = card.dataset.value;
    });
  });

  // --- Step 3 Logic: Garage Spaces ---
  if(inputGarage) {
      inputGarage.addEventListener('change', function() {
          if(this.value === 'Yes') {
              fieldGarageSpaces.classList.remove('hidden');
          } else {
              fieldGarageSpaces.classList.add('hidden');
              inputGarageSpaces.value = ''; // Clear value if hidden
          }
      });
  }

  // --- Step 4 Logic: E-Signature ---
  if(termsCheck){
    termsCheck.addEventListener('change', ()=>{
        if(termsCheck.checked){
            sigSection.style.display = 'block';
            submitBtn.disabled = false;
        } else {
            sigSection.style.display = 'none';
            submitBtn.disabled = true;
            sigInput.value = '';
        }
    });
  }

  // --- Terms Modal Logic ---
  if(termsLink){
      termsLink.addEventListener('click', (e) => {
          e.preventDefault();
          termsModal.classList.remove('hidden');
      });
  }
  if(termsClose){
      termsClose.addEventListener('click', () => {
          termsModal.classList.add('hidden');
      });
  }
  
  // --- Success Actions ---
  if(successHomeBtn){
      successHomeBtn.addEventListener('click', () => window.location.href='index.php');
  }
  if(successAgainBtn){
      successAgainBtn.addEventListener('click', () => window.location.href='?new=1');
  }

  // --- VALIDATION FUNCTION ---
  function validate(i){
    // --- CLEAR ERROR ON VALIDATE START ---
    qs('#err').classList.add('hidden');

    // Step 1: Personal Info
    if (i === 0) {
      const fn    = qs('#first_name').value.trim();
      const ln    = qs('#last_name').value.trim();
      const phone = qs('#phone').value.trim();
      const email = qs('#email').value.trim();
      if (!fn || !ln) { showError('First and last name are required'); return false; }
      if (!/^\d{10}$/.test(phone)) { showError('Phone must be exactly 10 digits'); return false; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('Enter a valid email'); return false; }
    }
    
    // Step 2: Property
    if (i === 1) {
        const ownLand = qs('#own_land').value;
        if (!ownLand) {
            showError('Please select whether you own the land or need help finding it.');
            return false;
        }
        if (ownLand === 'yes' && !qs('[name="land_address"]').value.trim()) {
            showError('Please enter the Land Address.');
            return false;
        }
        if (ownLand === 'no' && !qs('[name="preferred_location"]').value.trim()) {
            showError('Please enter a Preferred Location.');
            return false;
        }
    }

    // Step 3: Requirements
    if (i === 2) {
      const bd = qs('#bedrooms').value;
      const ba = qs('#bathrooms').value;
      if (!bd) { showError('Number of bedrooms is required'); return false; }
      if (!ba) { showError('Number of bathrooms is required'); return false; }
    }
    
    // Step 4: Terms & Signature
    if (i === 3) {
      const confirm = form.elements['confirm_accuracy'];
      const agree   = form.elements['agree_terms'];
      const sig     = qs('#digital_signature').value.trim();
      
      if (!sig) {
          showError('Please sign your request by typing your name in the Signature field.');
          return false;
      }
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

  // --- Navigation & Submission ---
  function handleNext() {
      // --- CLEAR ERRORS BEFORE SUBMITTING ---
      qs('#err').classList.add('hidden');

      if(!validate(current)) return; // STOP if validation fails
      
      const fd = new FormData(form);
      fd.append('action','save_step');
      fd.append('step', current + 1);
      if (submission_id) fd.append('submission_id', submission_id);

      const btn = (current === last) ? submitBtn : nextBtn;
      const originalText = btn.textContent;
      btn.textContent = "Processing...";
      btn.disabled = true;

      fetch('', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
          btn.disabled = false;
          btn.textContent = originalText;
          
          if(data.ok) {
              submission_id = data.submission_id;
              if (current < last) {
                  current++;
                  show(current);
              } else {
                  qs('#trackingNumber').textContent = data.tracking_code;
                  successPanel.classList.remove('hidden');
              }
          } else {
              alert(data.errors.join('\n'));
          }
      });
  }

  nextBtn.addEventListener('click', handleNext);
  submitBtn.addEventListener('click', handleNext);
  prevBtn.addEventListener('click', () => { if(current>0) { current--; show(current); } });

  // Init
  show(current);
})();
</script>
</body>
</html>