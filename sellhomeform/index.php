<?php
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
    
    // --- STEP 2: LOCATION & DETAILS (Merged) ---
    elseif ($step === 2) {
        // Location & Stats
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

        // Features
        $data['has_garage'] = isset($_POST['has_garage']) ? 1 : 0;
        $data['has_pool'] = isset($_POST['has_pool']) ? 1 : 0;
        $data['has_basement'] = isset($_POST['has_basement']) ? 1 : 0;
        $data['is_recently_updated'] = isset($_POST['is_recently_updated']) ? 1 : 0;
        $data['overall_condition'] = $get('overall_condition');
        $data['needs_repairs'] = $get('needs_repairs');

        // Selling Goals
        $data['is_listed'] = $get('is_listed');
        $data['current_price'] = $get('current_price');
        $data['desired_price'] = $get('desired_price');
        $data['sell_timeframe'] = $get('sell_timeframe');
        $data['sell_reason'] = $get('sell_reason');

        // New Step 2 Confirmations
        $data['confirm_owner'] = isset($_POST['confirm_owner']) ? 1 : 0;
        $data['confirm_accuracy_step2'] = isset($_POST['confirm_accuracy_step2']) ? 1 : 0;

        // Validation
        if (!$data['street_address']) $errors[] = "Address is required.";
        if (!$data['property_type']) $errors[] = "Property Type is required.";
        if ($data['bedrooms'] === null) $errors[] = "Bedrooms count is required.";
        if (!$data['sell_timeframe']) $errors[] = "Selling timeframe is required.";
        
        // // Validate new checkboxes
        // if (!$data['confirm_owner'] || !$data['confirm_accuracy_step2']) {
        //     $errors[] = "Please confirm ownership and accuracy before proceeding.";
        // }
    }
    
    // --- STEP 3: PHOTOS & T&C ---
    elseif ($step === 3) {
        $data['listing_link'] = $get('listing_link');
        $data['additional_info'] = $get('additional_info');
        $data['has_uploaded_photos'] = (!empty($_FILES['property_photos']['name'][0])) ? 1 : 0;
        
        // Final T&C Check
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

        jsonResponse([
            'ok' => true, 
            'submission_id' => $submission_id,
            'tracking_code' => $_SESSION['sell_tracking_code'] ?? $tracking_code ?? '',
            'received_data' => $data // Send data back to populate success modal
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
    
    /* Back Button */
    .back-btn {
        position: absolute; top: 20px; left: 170px;
        text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px;
        display: flex; align-items: center; gap: 5px;
        padding: 8px 16px; background: #fff; border: 1px solid var(--border); border-radius: 99px;
        transition: 0.2s;
    }
    .back-btn:hover { background: #f1f5f9; color: var(--text); }

    .container { max-width: 850px; margin: 80px auto 40px; padding: 0 20px; }
    
    /* Progress Indicators */
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

    /* Form Styles */
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

    /* Features Checkboxes */
    .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
    .checkbox-card {
        border: 1px solid var(--border); padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.2s;
    }
    .checkbox-card:hover { background: #f8fafc; }

    /* Confirmation Checkboxes (Step 2 & 3) */
    .terms-label { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; cursor: pointer; color: #334155; }
    .terms-label input { margin-top: 3px; accent-color: var(--primary); scale: 1.1; }
    .terms-box-final { background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid var(--border); }

    /* File Upload */
    .file-drop {
        border: 2px dashed #cbd5e1; border-radius: 8px; padding: 30px; text-align: center; background: #f8fafc; cursor: pointer; transition: 0.2s;
    }
    .file-drop:hover { border-color: var(--primary); background: #eff6ff; }

    /* Actions & States */
    .actions { display: flex; justify-content: space-between; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px; }
    .btn { padding: 10px 24px; border-radius: 6px; border: 1px solid var(--border); background: #fff; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .hidden { display: none !important; }
    .error-msg { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fecaca; }

    /* SUCCESS POPUP MODAL */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; z-index: 1000;
    }
    .modal-content {
        background: #fff; width: 95%; max-width: 550px; border-radius: 16px; padding: 40px 30px;
        text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: popUp 0.3s ease-out;
    }
    @keyframes popUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    
    .success-icon { width: 64px; height: 64px; background: #dcfce7; color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
    .tracking-box { background: #f1f5f9; padding: 12px; border-radius: 8px; margin: 20px auto; font-family: monospace; font-size: 18px; letter-spacing: 1px; color: var(--primary-dark); display: inline-block; }
    .summary-grid { text-align: left; background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; font-size: 14px; color: #475569; }
    .summary-grid strong { color: #0f172a; display: block; margin-bottom: 4px; }
    
    @media(max-width: 600px) { .grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<a href="/" class="back-btn">← Back to Home</a>

<div class="container" id="mainFormContainer">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 26px; font-weight: 700;">Request Free Valuation</h1>
        <p style="color: var(--text-muted); margin-top: 4px;">Complete the steps below to get your market analysis.</p>
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
                    <div class="section-title">👤 Your Contact Information</div>
                    <div style="background: #eff6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #1e40af; display: flex; gap: 10px;">
                        <span style="font-size: 18px;">ℹ️</span>
                        <div>Our experienced team will provide you with a comprehensive market analysis and help you sell your home for the best possible price.</div>
                    </div>
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
                    <div class="section-title">📍 Property Location & Details</div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="required">Street Address</label>
                        <input type="text" name="street_address" placeholder="123 Main Street">
                    </div>
                    
                    <div class="grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group"><label class="required">City</label><input type="text" name="city" placeholder="City"></div>
                        <div class="form-group"><label class="required">State</label><input type="text" name="state" placeholder="State"></div>
                        <div class="form-group"><label class="required">ZIP Code</label><input type="text" name="zip_code" placeholder="ZIP"></div>
                    </div>

                    <div class="grid">
                        <div class="form-group full-width">
                            <label class="required">Property Type</label>
                            <select name="property_type">
                                <option value="">Select property type</option>
                                <option value="Single Family">Single Family Home</option>
                                <option value="Condo">Condominium</option>
                                <option value="Townhouse">Townhouse</option>
                                <option value="Multi-Family">Villa</option>
                                <option value="Multi-Family">Land/Lot</option>


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
                            <label class="checkbox-card"><input type="checkbox" name="has_garage"> Garage</label>
                            <label class="checkbox-card"><input type="checkbox" name="has_basement"> Basement</label>
                            <label class="checkbox-card"><input type="checkbox" name="has_pool"> Pool</label>
                            <label class="checkbox-card"><input type="checkbox" name="is_recently_updated"> Recently Updated</label>
                        </div>
                    </div>

                    <div class="grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Overall Condition</label>
                            <select name="overall_condition">
                                <option value="">Select condition</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Needs Work</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Does it need major repairs?</label>
                            <select name="needs_repairs">
                                <option value="">Select</option>
                                <option value="No">No Major repairs needed</option>
                                <option value="minor_repair">Minor repairs</option>
                                <option value="Yes">Yes, major repairs needed</option>
                            </select>
                        </div>
                    </div>

                    <hr style="margin: 25px 0; border:0; border-top:1px solid var(--border);">
                    
                    <div class="section-title">💲 Selling Details</div>
                    <div class="form-group full-width">
                        <label class="required">Is your property currently listed?</label>
                        <select name="is_listed">
                            <option value="">Select</option>
                            <option value="No">No, not listed</option>
                            <option value="previous_expired">Previous listing expired</option>
                            <option value="Yes">Yes, currently listed</option>
                        </select>
                    </div>
                    <div class="grid">
                        <div class="form-group"><label>Current/Previous Asking Price</label><input type="text" name="current_price" placeholder="$500,000"></div>
                        <div class="form-group"><label>Desired Sale Price</label><input type="text" name="desired_price" placeholder="$525,000"></div>
                    </div>
                    <div class="form-group full-width">
                        <label class="required">When do you want to sell?</label>
                        <select name="sell_timeframe">
                            <option value="">Select timeframe</option>
                            <option value="ASAP">As soon as possible</option>
                            <option value="1-3 Months">1-3 Months</option>
                            <option value="3-6 Months">3-6 Months</option>
                            <option value="6+ Months">6-12 Months</option>
                            <option value="exploring">Just exploring options</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Reason for Selling (Optional)</label>
                        <select name="sell_reason">
                            <option value="">Select reason</option>
                            <option value="Upsizing">Upsizing</option>
                            <option value="Downsizing">Downsizing</option>
                            <option value="Relocation">Relocating</option>
                            <option value="Investment">Financial reasons</option>
                            <option value="not_to_say">Prefer not to say</option>
                        </select>
                    </div>

                    <!-- <div style="margin-top: 30px; border-top: 1px solid var(--border); padding-top: 25px;">
                        <label class="terms-label" style="margin-bottom: 12px;">
                            <input type="checkbox" name="confirm_owner" value="1">
                            <span style="font-weight: 500; color: #334155;">I confirm that I am the owner or authorized representative of this property.</span>
                        </label>
                        <label class="terms-label">
                            <input type="checkbox" name="confirm_accuracy_step2" value="1">
                            <span style="font-weight: 500; color: #334155;">I confirm that the information provided is accurate to the best of my knowledge.</span>
                        </label>
                    </div> -->
                </div>

                <div class="panel hidden" data-step="3">
                    <div class="section-title">🖼️ Property Photos & Links (Optional)</div>
                    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 14px;">Upload photos to help us provide a more accurate valuation.</p>
                    
                    <div class="form-group">
                        <label>Property Photos</label>
                        <div class="file-drop" onclick="document.getElementById('fileInput').click()">
                            <span style="font-size: 24px;">📷</span><br>
                            <span style="color: var(--primary); font-weight: 600;">Choose Files</span> or drag them here.
                            <input type="file" id="fileInput" name="property_photos[]" multiple style="display: none;" onchange="this.parentElement.querySelector('span:nth-child(2)').textContent = this.files.length + ' file(s) selected'">
                        </div>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">Upload exterior, interior, and any special features.</p>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <label>Zillow or Property Listing Link</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 12px; font-size: 16px;">🔗</span>
                            <input type="url" name="listing_link" placeholder="https://www.zillow.com/homedetails/..." style="padding-left: 35px;">
                        </div>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">Provide a link to your property on Zillow, Realtor.com, or other listing sites

</p>
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
        <div class="success-icon">✓</div>
        <h2 style="margin-bottom: 10px; color:#0f172a; font-size: 24px;">Request Submitted Successfully!</h2>
        <p style="color:#64748b; font-size:15px; line-height: 1.5; margin-bottom: 20px;">
            Thank you, <strong id="modalName" style="color:#0f172a;"></strong>. We have received your details and will contact you shortly.
        </p>
        
        <div style="font-size:12px; text-transform:uppercase; color:#94a3b8; font-weight:700; letter-spacing: 1px;">Your Tracking ID</div>
        <div class="tracking-box" id="modalTrack">...</div>
        
        <div class="summary-grid">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                <div><strong>Contact Email:</strong> <span id="modalEmail"></span></div>
                <div><strong>Phone:</strong> <span id="modalPhone"></span></div>
            </div>
            <div><strong>Property Address:</strong> <span id="modalAddress"></span></div>
        </div>

        <p style="color:#64748b; font-size:14px; margin-bottom:25px;">
            Our team will review your information and contact you within 24 hours to schedule a free property evaluation.
        </p>

        <a href="?new=1" class="btn btn-primary" style="display:inline-block; text-decoration:none; width:100%; padding: 12px;">Close & Submit Another Request</a>
        <br>
        <a href="/" style="display:inline-block; margin-top:15px; color:#64748b; text-decoration:none; font-size:14px; font-weight: 500;">Return to Home</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 3;
    
    const panels = document.querySelectorAll('.panel');
    const bubbles = document.querySelectorAll('.step-item');
    const progressFill = document.getElementById('progressBar');
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const errorBox = document.getElementById('errorBox');
    const successModal = document.getElementById('successModal');
    const form = document.getElementById('listingForm');
    
    function updateUI() {
        // Show current panel
        panels.forEach(p => p.classList.toggle('hidden', parseInt(p.dataset.step) !== currentStep));
        
        // Update Progress Bar width
        const percent = (currentStep / totalSteps) * 100;
        progressFill.style.width = percent + '%';

        // Update Buttons visibility
        prevBtn.classList.toggle('hidden', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === totalSteps);
        submitBtn.classList.toggle('hidden', currentStep !== totalSteps);

        // Update Step Bubbles
        bubbles.forEach((b, index) => {
            const stepNum = index + 1;
            b.classList.remove('active', 'done');
            if (stepNum === currentStep) {
                b.classList.add('active');
                b.querySelector('.step-bubble').textContent = stepNum;
            } else if (stepNum < currentStep) {
                b.classList.add('done');
                b.querySelector('.step-bubble').innerHTML = '✓';
            } else {
                b.querySelector('.step-bubble').textContent = stepNum;
            }
        });
        // Hide error box on step change
        errorBox.classList.add('hidden');
        // Scroll to top of form
        document.getElementById('mainFormContainer').scrollIntoView({ behavior: 'smooth' });
    }

    function handleSubmission() {
        const formData = new FormData(form);
        formData.append('action', 'save_step');
        formData.append('step', currentStep);

        const btn = (currentStep === totalSteps) ? submitBtn : nextBtn;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = "Processing...";
        errorBox.classList.add('hidden');

        fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = originalText;

            if (data.ok) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateUI();
                } else {
                    // Final Step Success - Show Modal
                    const d = data.received_data;
                    document.getElementById('modalName').textContent = d.contact_name || 'Customer';
                    document.getElementById('modalEmail').textContent = d.contact_email || '-';
                    document.getElementById('modalPhone').textContent = d.contact_phone || '-';
                    document.getElementById('modalAddress').textContent = (d.street_address || '') + ' ' + (d.city || '');
                    document.getElementById('modalTrack').textContent = data.tracking_code;
                    successModal.classList.remove('hidden');
                }
            } else {
                // Show Errors
                errorBox.innerHTML = data.errors.join('<br>');
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.textContent = originalText;
            errorBox.textContent = "Connection error. Please check your internet and try again.";
            errorBox.classList.remove('hidden');
        });
    }

    // Event Listeners
    nextBtn.addEventListener('click', handleSubmission);
    submitBtn.addEventListener('click', handleSubmission);
    prevBtn.addEventListener('click', () => { 
        if(currentStep > 1) { currentStep--; updateUI(); } 
    });
    
    // Allow navigation by clicking completed step bubbles
    bubbles.forEach((b, idx) => {
        b.addEventListener('click', () => {
            if (idx + 1 < currentStep) {
                currentStep = idx + 1;
                updateUI();
            }
        });
    });

    // Initialize UI
    updateUI();
});
</script>
</body>
</html>