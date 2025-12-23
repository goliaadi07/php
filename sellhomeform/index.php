<?php
// index.php - Sell Property Form
session_start();
require_once __DIR__ . '/db.php'; // Ensure db.php exists with $pdo connection

// Reset handler for "Close & New Request" button
if (isset($_GET['new']) && $_GET['new'] === '1') {
    unset($_SESSION['sell_submission_id'], $_SESSION['sell_tracking_code']);
    header("Location: index.php");
    exit;
}

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$submission_id = $_SESSION['sell_submission_id'] ?? 0;

// ================= AJAX HANDLER =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_step') {
    
    if (!isset($pdo)) jsonResponse(['ok' => false, 'errors' => ['Database connection failed']]);

    $step = (int)$_POST['step']; 
    $errors = [];
    $data = [];
    
    $get = fn($k) => isset($_POST[$k]) ? trim($_POST[$k]) : null;
    $getNum = fn($k) => (isset($_POST[$k]) && trim($_POST[$k]) !== '') ? trim($_POST[$k]) : null;

    // --- STEP 1: CONTACT INFO ---
    if ($step === 1) {
        $data['contact_name'] = $get('contact_name');
        $data['contact_phone'] = $get('contact_phone');
        $data['contact_email'] = $get('contact_email');

        if (!$data['contact_name']) $errors[] = "Full Name is required.";
        if (!$data['contact_phone']) $errors[] = "Phone Number is required.";
        if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid Email is required.";
    }
    
    // --- STEP 2: LOCATION & DETAILS ---
    elseif ($step === 2) {
        $data['street_address'] = $get('street_address');
        $data['city'] = $get('city');
        $data['state'] = $get('state');
        $data['zip_code'] = $get('zip_code');
        $data['property_type'] = $get('property_type');
        $data['bedrooms'] = $getNum('bedrooms');
        $data['bathrooms'] = $getNum('bathrooms');
        $data['square_feet'] = $getNum('square_feet'); 
        $data['year_built'] = $getNum('year_built');
        $data['lot_size'] = $get('lot_size');

        // Checkboxes & Conditional Logic
        $data['has_garage'] = isset($_POST['has_garage']) ? 1 : 0;
        $data['garage_count'] = ($data['has_garage'] == 1) ? $getNum('garage_count') : 0; // Save count only if checked

        $data['has_pool'] = isset($_POST['has_pool']) ? 1 : 0;
        $data['pool_count'] = ($data['has_pool'] == 1) ? $getNum('pool_count') : 0;

        $data['has_basement'] = isset($_POST['has_basement']) ? 1 : 0;
        $data['basement_area'] = ($data['has_basement'] == 1) ? $getNum('basement_area') : 0;

        $data['is_recently_updated'] = isset($_POST['is_recently_updated']) ? 1 : 0;
        
        $data['overall_condition'] = $get('overall_condition');
        $data['needs_repairs'] = $get('needs_repairs');

        $data['is_listed'] = $get('is_listed');
        $data['current_price'] = $get('current_price');
        $data['desired_price'] = $get('desired_price');
        $data['sell_timeframe'] = $get('sell_timeframe');
        $data['sell_reason'] = $get('sell_reason');

        if (!$data['street_address']) $errors[] = "Address is required.";
        if (!$data['property_type']) $errors[] = "Property Type is required.";
        if ($data['bedrooms'] === null) $errors[] = "Bedrooms count is required.";
        if (!$data['sell_timeframe']) $errors[] = "Selling timeframe is required.";
        
        // Validate garage count if garage is checked
        if ($data['has_garage'] == 1 && empty($data['garage_count'])) {
             $errors[] = "Please specify the number of garage spaces.";
        }
        if ($data['has_basement'] == 1 && empty($data['basement_area'])) {
             $errors[] = "Please specify the basement area.";
        }
        if ($data['has_pool'] == 1 && empty($data['pool_count'])) {
             $errors[] = "Please specify the number of pools.";
        }
    }
    
    // --- STEP 3: PHOTOS & T&C ---
    elseif ($step === 3) {
        $data['listing_link'] = $get('listing_link');
        $data['additional_info'] = $get('additional_info');
        
        // --- IMAGE UPLOAD LOGIC ---
        $uploaded_files = [];
        if (!empty($_FILES['property_photos']['name'][0])) {
            $target_dir = __DIR__ . '/sellfolder/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            foreach ($_FILES['property_photos']['name'] as $key => $name) {
                if ($_FILES['property_photos']['error'][$key] === 0) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $new_filename = 'PROP_' . time() . '_' . uniqid() . '.' . $ext;
                    $target_file = $target_dir . $new_filename;

                    if (move_uploaded_file($_FILES['property_photos']['tmp_name'][$key], $target_file)) {
                        $uploaded_files[] = $new_filename;
                    }
                }
            }
        }

        if (!empty($uploaded_files)) {
            $data['photo_path'] = implode(',', $uploaded_files);
            $data['has_uploaded_photos'] = 1;
        } else {
            $data['has_uploaded_photos'] = 0;
        }

        if (!isset($_POST['agree_terms']) || $_POST['agree_terms'] !== '1') {
            $errors[] = "You must agree to the final Terms and Conditions.";
        }
    }

    if (!empty($errors)) jsonResponse(['ok' => false, 'errors' => $errors]);

    try {
        if ($submission_id > 0) {
            // Update existing record
            $setClauses = [];
            $params = [':id' => $submission_id];
            foreach ($data as $key => $val) {
                $setClauses[] = "`$key` = :$key";
                $params[":$key"] = $val;
            }
            $setClauses[] = "last_completed_step = GREATEST(last_completed_step, :step)";
            $params[':step'] = $step;

            $sql = "UPDATE sell_requests SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // Insert new record
            $cols = array_keys($data);
            $vals = array_values($data);
            $tracking_code = 'PROP-' . strtoupper(substr(uniqid(), -6));
            
            $placeholders = array_map(fn($c) => ":$c", $cols);
            
            $sql = "INSERT INTO sell_requests (" . implode(',', $cols) . ", tracking_code, last_completed_step) 
                    VALUES (" . implode(',', $placeholders) . ", :track, :step)";
            
            $params = array_combine($placeholders, $vals);
            $params[':track'] = $tracking_code;
            $params[':step'] = $step;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $submission_id = $pdo->lastInsertId();
            $_SESSION['sell_submission_id'] = $submission_id;
            $_SESSION['sell_tracking_code'] = $tracking_code;
        }

        // On Final Step, fetch full data for the summary popup
        $full_summary = [];
        if ($step === 3) {
            $stmt = $pdo->prepare("SELECT * FROM sell_requests WHERE id = ?");
            $stmt->execute([$submission_id]);
            $full_summary = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        jsonResponse([
            'ok' => true, 
            'submission_id' => $submission_id,
            'tracking_code' => $_SESSION['sell_tracking_code'] ?? $tracking_code ?? '',
            'full_data' => $full_summary
        ]);

    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'errors' => ['DB Error: ' . $e->getMessage()]]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sell Your Property</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --success: #22c55e;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --text: #0f172a;
        --text-muted: #64748b;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 40px; }
    
    .back-btn {
        position: absolute; top: 20px; left: 170px;
        text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px;
        display: flex; align-items: center; gap: 5px;
        padding: 8px 16px; background: #fff; border: 1px solid var(--border); border-radius: 99px;
        transition: 0.2s;
    }
    .back-btn:hover { background: #f1f5f9; color: var(--text); }

    .container { max-width: 850px; margin: 80px auto 40px; padding: 0 20px; }
    
    .steps-row { display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; max-width: 600px; margin-left: auto; margin-right: auto; }
    .step-item { display: flex; flex-direction: column; align-items: center; z-index: 2; cursor: pointer; flex: 1; }
    .step-bubble {
        width: 36px; height: 36px; border-radius: 50%; background: #fff; border: 2px solid #cbd5e1;
        display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-muted);
        transition: all 0.3s;
    }
    .step-label { font-size: 13px; margin-top: 8px; color: var(--text-muted); font-weight: 500; }
    .step-item.active .step-bubble { border-color: var(--primary); color: var(--primary); background: #eff6ff; }
    .step-item.active .step-label { color: var(--primary); font-weight: 600; }
    .step-item.done .step-bubble { background: var(--success); border-color: var(--success); color: #fff; }
    
    .progress-track {
        height: 6px; background: #e2e8f0; border-radius: 4px; margin: 20px auto 30px; 
        max-width: 600px; overflow: hidden;
    }
    .progress-fill { background: var(--primary); height: 100%; width: 33%; transition: width 0.4s ease; }

    .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid var(--border); }
    .card-body { padding: 30px; }
    .section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .full-width { grid-column: span 2; }
    .form-group label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #334155; }
    .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"], .form-group input[type="number"], .form-group input[type="url"], .form-group select, .form-group textarea {
        width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; background: #f8fafc;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); background: #fff; }
    .required::after { content: " *"; color: #ef4444; }

    .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
    .checkbox-card {
        border: 1px solid var(--border); padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.2s;
    }
    .checkbox-card:hover { background: #f8fafc; }

    .terms-label { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; cursor: pointer; color: #334155; }
    .terms-label input { margin-top: 3px; accent-color: var(--primary); scale: 1.1; }
    .terms-box-final { background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid var(--border); }

    /* Info Blue Box */
    .blue-info-box {
        background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; 
        font-size: 14px; color: #1e40af; display: flex; gap: 10px; border: 1px solid #bfdbfe;
    }
    .blue-info-box ul { margin: 0; padding-left: 20px; margin-top: 5px; }
    .blue-info-box li { margin-bottom: 3px; }

    /* Conditional Garage Input */
    .garage-input-container {
        display: none; /* Hidden by default */
        margin-top: 10px;
        padding-left: 5px;
        width: 100%;
    }
    .garage-input-container.visible { display: block; animation: slideDown 0.2s ease-out; }
    @keyframes slideDown { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }

    .file-drop {
        border: 2px dashed #cbd5e1; border-radius: 8px; padding: 30px; text-align: center; background: #f8fafc; cursor: pointer; transition: 0.2s;
    }
    .file-drop:hover { border-color: var(--primary); background: #eff6ff; }
    .file-drop.file-selected { border-color: var(--success); background: #f0fdf4; border-style: solid; }

    .replace-btn {
        display: inline-block; margin-top: 10px; padding: 6px 16px; background: #fff; border: 1px solid var(--border);
        border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--text-muted); transition: 0.2s;
    }
    .file-drop:hover .replace-btn { background: var(--primary); color: white; border-color: var(--primary); }

    .actions { display: flex; justify-content: space-between; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px; }
    .btn { padding: 10px 24px; border-radius: 6px; border: 1px solid var(--border); background: #fff; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .hidden { display: none !important; }
    .error-msg { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fecaca; }

    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; z-index: 1000;
    }
    .modal-content {
        background: #fff; width: 95%; max-width: 600px; border-radius: 16px; 
        max-height: 90vh; overflow-y: auto; padding: 0;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: popUp 0.3s ease-out;
        text-align: left;
    }
    .modal-header { padding: 25px; border-bottom: 1px solid #e2e8f0; text-align: center; }
    .modal-body { padding: 25px; }
    @keyframes popUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    
    .success-icon { width: 64px; height: 64px; background: #dcfce7; color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
    .tracking-box { background: #f1f5f9; padding: 12px; border-radius: 8px; margin: 20px auto; font-family: monospace; font-size: 18px; letter-spacing: 1px; color: var(--primary-dark); display: inline-block; }
    .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; font-size: 14px; color: #334155; }
    .summary-section { margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
    .summary-section:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }
    .summary-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
    .tag { display: inline-block; background: #eff6ff; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-right: 5px; margin-top: 3px; }
    
    .success-list { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 12px; margin: 20px 0; }
    .success-list h4 { margin: 0 0 10px; color: #166534; font-size: 15px; }
    .success-list ul { margin: 0; padding-left: 20px; font-size: 14px; color: #14532d; line-height: 1.6; }
    .success-list li { margin-bottom: 5px; }

    @media(max-width: 600px) { .grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<a href="/" class="back-btn">← Back to Home</a>

<div class="container" id="mainFormContainer">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="font-size: 16px; font-weight: 700;">List Your Property</h3>
        <p style="color: var(--text-muted); margin-top: 4px;">Request to list your property with us. Get a free home valuation and expert marketing support.</p>
    </div>

    <div class="steps-row">
        <div class="step-item active" data-step="1">
            <div class="step-bubble">1</div>
            <div class="step-label">Contact</div>
        </div>
        <div class="step-item" data-step="2">
            <div class="step-bubble">2</div>
            <div class="step-label">Details</div>
        </div>
        <div class="step-item" data-step="3">
            <div class="step-bubble">3</div>
            <div class="step-label">Photos & T&C</div>
        </div>
    </div>
    
    <div class="progress-track">
        <div class="progress-fill" id="progressBar" style="width: 33%"></div>
    </div>

    <form id="listingForm" enctype="multipart/form-data">
        <div class="card">
            <div class="card-body">
                <div id="errorBox" class="error-msg hidden"></div>

                <div class="panel" data-step="1">
                    <div class="blue-info-box">
                        <span style="font-size: 18px;">ℹ️</span>
                        <div>
                            <strong>Submit your property listing request.</strong>
                            Our experienced team will provide you with a comprehensive market analysis and help you list and sell your home for the best possible price.
                            <ul>
                                <li>Request to upload and list your property</li>
                                <li>Free home valuation and market analysis</li>
                                <li>Professional photography and marketing</li>
                                <li>Expert negotiation and closing support</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section-title">👤 Your Contact Information</div>
                    <div class="grid">
                        <div class="form-group">
                            <label class="required">Full Name</label>
                            <input type="text" name="contact_name" placeholder="John Doe">
                        </div>
                        <div class="form-group">
                            <label class="required">Phone Number</label>
                            <input type="tel" name="contact_phone" placeholder="+1 (555) 123-4567">
                        </div>
                        <div class="form-group full-width">
                            <label class="required">Email Address</label>
                            <input type="email" name="contact_email" placeholder="johndoe@email.com">
                        </div>
                    </div>
                </div>

                <div class="panel hidden" data-step="2">
                    <div class="blue-info-box">
                        <span style="font-size: 18px;">ℹ️</span>
                        <div>Provide accurate property details to help us generate a precise initial valuation for your home.</div>
                    </div>

                    <div class="section-title">📍 Property Location & Details</div>
                    <div class="form-group" style="margin-bottom: 15px;"><label class="required">Street Address</label><input type="text" name="street_address" placeholder="123 Main Street"></div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group"><label class="required">City</label><input type="text" name="city" placeholder="City"></div>
                        <div class="form-group"><label class="required">State</label><input type="text" name="state" placeholder="State"></div>
                        <div class="form-group"><label class="required">ZIP Code</label><input type="text" name="zip_code" placeholder="ZIP"></div>
                    </div>

                    <div class="grid">
                        <div class="form-group full-width"><label class="required">Property Type</label>
                            <select name="property_type">
                                <option value="">Select property type</option>
                                <option value="Single Family">Single Family Home</option>
                                <option value="Condo">Condominium</option>
                                <option value="Townhouse">Townhouse</option>
                                <option value="Villa">Villa</option>
                                <option value="Land">Land</option>
                            </select>
                        </div>
                        <div class="form-group"><label class="required">Bedrooms</label><input type="number" name="bedrooms" placeholder="3"></div>
                        <div class="form-group"><label>Bathrooms</label><input type="number" name="bathrooms" placeholder="2"></div>
                        <div class="form-group"><label>Square Feet</label><input type="number" name="square_feet" placeholder="2000"></div>
                        <div class="form-group"><label>Year Built</label><input type="number" name="year_built" placeholder="2015"></div>
                    </div>
                    <div class="form-group"><label>Lot Size</label><input type="text" name="lot_size" placeholder="e.g. 0.25 acres"></div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label>Property Features</label>
                        <div class="checkbox-grid">
                            <label class="checkbox-card">
                                <input type="checkbox" name="has_garage" id="chkGarage" onchange="toggleFeature('chkGarage', 'garageInput')"> Garage
                            </label>
                            
                            <label class="checkbox-card">
                                <input type="checkbox" name="has_basement" id="chkBasement" onchange="toggleFeature('chkBasement', 'basementInput')"> Basement
                            </label>
                            
                            <label class="checkbox-card">
                                <input type="checkbox" name="has_pool" id="chkPool" onchange="toggleFeature('chkPool', 'poolInput')"> Pool
                            </label>
                            
                            <label class="checkbox-card"><input type="checkbox" name="is_recently_updated"> Recently Updated</label>
                        </div>
                        
                        <div id="garageInput" class="conditional-input-container">
                            <label style="font-size:13px; color:#2563eb; margin-bottom:4px; display:block;">How many garage spaces?</label>
                            <input type="number" name="garage_count" placeholder="e.g., 2">
                        </div>
                        
                        <div id="basementInput" class="conditional-input-container">
                            <label style="font-size:13px; color:#2563eb; margin-bottom:4px; display:block;">Basement Area (sq ft)?</label>
                            <input type="number" name="basement_area" placeholder="e.g., 800">
                        </div>
                        
                        <div id="poolInput" class="conditional-input-container">
                            <label style="font-size:13px; color:#2563eb; margin-bottom:4px; display:block;">Number of Pools?</label>
                            <input type="number" name="pool_count" placeholder="e.g., 1">
                        </div>
                    </div>

                    <div class="grid" style="margin-top: 20px;">
                        <div class="form-group"><label>Overall Condition</label><select name="overall_condition"><option value="">Select condition</option><option value="Excellent">Excellent</option><option value="Good">Good</option><option value="Fair">Fair</option><option value="Poor">Poor</option></select></div>
                        <div class="form-group"><label>Does it need major repairs?</label><select name="needs_repairs"><option value="">Select</option><option value="No">No</option><option value="minor_repair">Minor</option><option value="Yes">Major</option></select></div>
                    </div>

                    <hr style="margin: 25px 0; border:0; border-top:1px solid var(--border);">
                    <div class="section-title">💲 Selling Details</div>
                    <div class="form-group full-width"><label class="required">Is your property currently listed?</label><select name="is_listed"><option value="">Select</option><option value="No">No</option><option value="Yes">Yes</option></select></div>
                    <div class="grid">
                        <div class="form-group"><label>Current Asking Price</label><input type="text" name="current_price" placeholder="$500,000"></div>
                        <div class="form-group"><label>Desired Sale Price</label><input type="text" name="desired_price" placeholder="$525,000"></div>
                    </div>
                    <div class="form-group full-width"><label class="required">When do you want to sell?</label><select name="sell_timeframe"><option value="">Select timeframe</option><option value="ASAP">ASAP</option><option value="1-3 Months">1-3 Months</option><option value="3-6 Months">3-6 Months</option><option value="6+ Months">6+ Months</option></select></div>
                    <div class="form-group full-width"><label>Reason for Selling (Optional)</label><select name="sell_reason"><option value="">Select reason</option><option value="Upsizing">Upsizing</option><option value="Downsizing">Downsizing</option><option value="Relocation">Relocating</option><option value="Investment">Investment</option></select></div>
                </div>

                <div class="panel hidden" data-step="3">
                    <div class="blue-info-box">
                        <span style="font-size: 18px;">ℹ️</span>
                        <div>Almost done! Uploading photos helps us market your property faster. Review the terms to complete your request.</div>
                    </div>

                    <div class="section-title">🖼️ Property Photos & Links (Optional)</div>
                    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 14px;">Upload photos to help us provide a more accurate valuation.</p>
                    
                    <div class="form-group">
                        <label>Property Photos</label>
                        
                        <div class="file-drop" id="dropArea" onclick="document.getElementById('fileInput').click()">
                            <span style="font-size: 24px;">📷</span><br>
                            <span style="color: var(--primary); font-weight: 600;">Choose Files</span> or drag them here.
                            
                            <input type="file" id="fileInput" name="property_photos[]" multiple style="display: none;">
                        </div>

                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">Upload exterior, interior, and any special features.</p>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <label>Zillow or Property Listing Link</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 12px; font-size: 16px;">🔗</span>
                            <input type="url" name="listing_link" placeholder="https://..." style="padding-left: 35px;">
                        </div>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">Provide a link to your property on Zillow, Realtor.com, or other listing sites</p>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <label>Additional Information</label>
                        <textarea name="additional_info" rows="4" placeholder="Tell us about recent upgrades, unique features, neighborhood amenities, or any other important details..."></textarea>
                    </div>

                    <div class="terms-box-final">
                        <label class="terms-label">
                            <input type="checkbox" name="agree_terms" value="1">
                            <span>I Confirm that I am the owner or authorized representative of the property.</span>
                        </label>
                    </div>
                    <div class="terms-box-final">
                        <label class="terms-label">
                            <input type="checkbox" name="agree_terms" value="1">
                            <span>I Confirm that the information provided is accurate to the best of my knowledge.</span>
                        </label>
                    </div>
                </div>

                <div class="actions">
                    <button type="button" id="prevBtn" class="btn hidden">← Previous</button>
                    <button type="button" id="nextBtn" class="btn btn-primary">Next Step</button>
                    <button type="button" id="submitBtn" class="btn btn-primary hidden">Request Free Valuation</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div id="successModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <div style="font-size:40px; color:#22c55e; margin-bottom:10px;">✓</div>
            <h2 style="margin:0; font-size:22px; color:#0f172a;">Request Submitted!</h2>
            <div style="font-size:14px; color:#64748b; margin-top:5px;">Thank you. We have received your details.</div>
        </div>
        <div class="modal-body">
            <div class="tracking-box">
                <span style="font-size:12px; color:#64748b; text-transform:uppercase;">Tracking ID</span><br>
                <span id="sumTrack" class="tracking-code">...</span>
            </div>

            <div class="summary-card">
                <div class="summary-section">
                    <div class="summary-label">Your Contact Information</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div><strong>Email:</strong> <span id="sumEmail">-</span></div>
                        <div><strong>Phone:</strong> <span id="sumPhone">-</span></div>
                    </div>
                </div>
                <div class="summary-section">
                    <div class="summary-label">Property Details</div>
                    <div><strong>Address:</strong> <span id="sumAddress">-</span></div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:5px; margin-top:5px;">
                        <div><strong>Beds:</strong> <span id="sumBeds">-</span></div>
                        <div><strong>Baths:</strong> <span id="sumBaths">-</span></div>
                        <div><strong>Sq Ft:</strong> <span id="sumSq">-</span></div>
                        <div><strong>Year:</strong> <span id="sumYear">-</span></div>
                    </div>
                    <div style="margin-top:5px;"><strong>Features:</strong> <span id="sumFeatures">-</span></div>
                </div>
                <div class="summary-section">
                    <div class="summary-label">Selling Details</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:5px;">
                        <div><strong>Listed:</strong> <span id="sumListed">-</span></div>
                        <div><strong>Timeframe:</strong> <span id="sumTime">-</span></div>
                    </div>
                </div>
            </div>

            <div class="success-list">
                <h4>What happens next:</h4>
                <ul>
                    <li>Our agent will call you within 24 hours to schedule a property visit</li>
                    <li>We'll provide a comprehensive free market analysis</li>
                    <li>You'll receive a competitive offer based on current market conditions</li>
                    <li>Get expert guidance on preparing your home for sale</li>
                    <li>Professional photography and marketing support included</li>
                </ul>
            </div>

            <a href="?new=1" class="btn btn-primary" style="width:100%; display:block; text-align:center; text-decoration:none; box-sizing:border-box; margin-bottom: 10px;">Submit Another Request</a>
            
            <a href="index.php" class="btn" style="width:100%; display:block; text-align:center; text-decoration:none; box-sizing:border-box; background: #f1f5f9; border-color: #cbd5e1; color: #475569;">Close & Return to Home</a>
        </div>
    </div>
</div>

<script>
    function toggleFeature(chkId, inputId) {
        const chk = document.getElementById(chkId);
        const box = document.getElementById(inputId);
        if(chk.checked) {
            box.classList.add('visible');
            box.querySelector('input').focus();
        } else {
            box.classList.remove('visible');
            box.querySelector('input').value = '';
        }
    }

document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 3;
    const form = document.getElementById('listingForm');
    
    // Elements
    const panels = document.querySelectorAll('.panel');
    const bubbles = document.querySelectorAll('.step-item');
    const progressFill = document.getElementById('progressBar');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const errorBox = document.getElementById('errorBox');
    const successModal = document.getElementById('successModal');

    // File UI
    const fileInput = document.getElementById('fileInput');
    const dropArea = document.getElementById('dropArea');
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const count = this.files.length;
            const text = count > 1 ? count + ' files selected' : this.files[0].name;
            const children = Array.from(dropArea.children);
            children.forEach(c => { if(c.id !== 'fileInput') c.remove(); });
            const div = document.createElement('div');
            div.innerHTML = `<div style="color:var(--success);font-weight:700;">✓ Ready</div><div style="font-size:13px;color:#64748b;">${text}</div><div class="replace-btn">Replace</div>`;
            dropArea.prepend(div);
            dropArea.classList.add('file-selected');
        }
    });

    function updateUI() {
        panels.forEach(p => p.classList.toggle('hidden', parseInt(p.dataset.step) !== currentStep));
        progressFill.style.width = ((currentStep/totalSteps)*100) + '%';
        prevBtn.classList.toggle('hidden', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === totalSteps);
        submitBtn.classList.toggle('hidden', currentStep !== totalSteps);
        
        bubbles.forEach((b, i) => {
            const num = i + 1;
            b.classList.remove('active', 'done');
            if(num === currentStep) b.classList.add('active');
            else if(num < currentStep) b.classList.add('done');
        });
        errorBox.classList.add('hidden');
        window.scrollTo(0,0);
    }

    function handleSubmission() {
        const fd = new FormData(form);
        fd.append('action', 'save_step');
        fd.append('step', currentStep);

        const btn = (currentStep === totalSteps) ? submitBtn : nextBtn;
        const txt = btn.textContent;
        btn.disabled = true;
        btn.textContent = "Processing...";
        errorBox.classList.add('hidden');

        fetch('', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.textContent = txt;
            if(res.ok) {
                if(currentStep < totalSteps) {
                    currentStep++;
                    updateUI();
                } else {
                    // Populate Summary
                    const d = res.full_data;
                    document.getElementById('sumTrack').textContent = res.tracking_code;
                    document.getElementById('sumEmail').textContent = d.contact_email;
                    document.getElementById('sumPhone').textContent = d.contact_phone;
                    document.getElementById('sumAddress').textContent = d.street_address + ', ' + d.city;
                    document.getElementById('sumBeds').textContent = d.bedrooms;
                    document.getElementById('sumBaths').textContent = d.bathrooms;
                    document.getElementById('sumSq').textContent = d.square_feet + ' sq ft';
                    document.getElementById('sumYear').textContent = d.year_built;
                    document.getElementById('sumListed').textContent = d.is_listed;
                    document.getElementById('sumTime').textContent = d.sell_timeframe;

                    // Features Logic
                    let feats = [];
                    if(d.has_garage == 1) feats.push('Garage ('+d.garage_count+')');
                    if(d.has_basement == 1) feats.push('Basement ('+d.basement_area+' sqft)');
                    if(d.has_pool == 1) feats.push('Pool ('+d.pool_count+')');
                    if(d.is_recently_updated == 1) feats.push('Updated');
                    
                    const featContainer = document.getElementById('sumFeatures');
                    featContainer.innerHTML = '';
                    if(feats.length > 0) {
                        feats.forEach(f => {
                            featContainer.innerHTML += `<span class="tag">✓ ${f}</span>`;
                        });
                    } else {
                        featContainer.textContent = "None";
                    }

                    successModal.classList.remove('hidden');
                }
            } else {
                errorBox.innerHTML = res.errors.join('<br>');
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })
        .catch(e => {
            console.error(e);
            btn.disabled = false;
            btn.textContent = txt;
            errorBox.textContent = "Network Error.";
            errorBox.classList.remove('hidden');
        });
    }

    nextBtn.addEventListener('click', handleSubmission);
    submitBtn.addEventListener('click', handleSubmission);
    prevBtn.addEventListener('click', () => { if(currentStep > 1) { currentStep--; updateUI(); } });
    bubbles.forEach((b, i) => b.addEventListener('click', () => {
        if(i + 1 < currentStep) { currentStep = i + 1; updateUI(); }
    }));

    updateUI();
});
</script>
</body>
</html>