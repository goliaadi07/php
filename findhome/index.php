<?php
// index.php - Handle Form Submission

// 1. DATABASE CONNECTION SETTINGS
$servername = "localhost";
$username = "root";
$password = "aditya29";        // Your password
$dbname = "homebuilder_app";   // Your existing database

$success = false;
$error_msg = "";
$tracking_id = ""; // Variable to store the new ID

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $contact_method = $_POST['contact_method'] ?? '';
    
    $property_type = $_POST['property_type'] ?? '';
    $min_price = $_POST['min_price'] ?? 0;
    $max_price = $_POST['max_price'] ?? 0;
    $min_bedrooms = $_POST['min_bedrooms'] ?? '';
    $min_bathrooms = $_POST['min_bathrooms'] ?? '';
    $min_sq_feet = $_POST['min_sq_feet'] ?? 0;
    
    // --- FEATURES LOGIC ---
    $features_list = [];
    if (isset($_POST['feat_garage'])) {
        $detail = $_POST['detail_garage'] ? " (" . htmlspecialchars($_POST['detail_garage']) . " cars)" : "";
        $features_list[] = "Garage" . $detail;
    }
    if (isset($_POST['feat_basement'])) {
        $detail = $_POST['detail_basement'] ? " (" . htmlspecialchars($_POST['detail_basement']) . ")" : "";
        $features_list[] = "Basement" . $detail;
    }
    if (isset($_POST['feat_kitchen'])) {
        $detail = $_POST['detail_kitchen'] ? " (" . htmlspecialchars($_POST['detail_kitchen']) . ")" : "";
        $features_list[] = "Updated Kitchen" . $detail;
    }
    if (isset($_POST['feat_backyard'])) {
        $detail = $_POST['detail_backyard'] ? " (" . htmlspecialchars($_POST['detail_backyard']) . ")" : "";
        $features_list[] = "Backyard" . $detail;
    }
    $features = implode(", ", $features_list);
    
    $preferred_locations = $_POST['preferred_locations'] ?? '';
    $additional_reqs = $_POST['additional_reqs'] ?? '';
    $pre_approved = $_POST['pre_approved'] ?? '';
    $first_time_buyer = $_POST['first_time_buyer'] ?? '';
    $buy_timeline = $_POST['buy_timeline'] ?? '';
    $digital_signature = $_POST['digital_signature'] ?? "Agreed via Checkbox"; 

    // --- GENERATE TRACKING ID ---
    $tracking_id = "REQ-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    // SQL Query
    $sql = "INSERT INTO property_requests (
        tracking_id, first_name, last_name, email, phone, contact_method,
        property_type, min_price, max_price, min_bedrooms, min_bathrooms, min_sq_feet, features,
        preferred_locations, additional_reqs,
        pre_approved, first_time_buyer, buy_timeline, digital_signature
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // 19 types for 19 variables
        $stmt->bind_param("sssssssssssisssssss", 
            $tracking_id, $first_name, $last_name, $email, $phone, $contact_method,
            $property_type, $min_price, $max_price, $min_bedrooms, $min_bathrooms, $min_sq_feet, $features,
            $preferred_locations, $additional_reqs,
            $pre_approved, $first_time_buyer, $buy_timeline, $digital_signature
        );

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_msg = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = "Prepare failed: " . $conn->error;
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Dream Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --orange: #f37021;
            --orange-dark: #d35400;
            --red: #ee2a24;
            --pink: #e91e63;
            --bg-cream: #fef9f0;
            --gray-light: #f4f4f4;
            --text-dark: #333;
            --text-muted: #94a3b8;
            --success: #22c55e;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-cream);
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .main-container {
            width: 100%;
            max-width: 900px;
            padding: 20px;
        }

        .top-header { text-align: center; margin-bottom: 30px; }
        .top-header h2 { margin: 0 0 10px; color: #333; }
        
        .browse-btn {
            margin-top: 20px;
            background-color: #ffffff;
            border: 1px solid #f37021;
            color: #333;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .browse-btn:hover {
            background-color: #fff8f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 112, 33, 0.2);
        }

        .browse-btn i { color: #f37021; }

        /* --- PROGRESS BAR STYLING --- */
        .top-progress { 
            height: 10px; 
            background: #e2e8f0; 
            border-radius: 999px; 
            margin: 16px auto 20px; 
            overflow: hidden; 
            max-width: 800px;
        }
        .top-progress .fill { 
            height: 100%; 
            width: 0; 
            background: linear-gradient(90deg, #1f2937, var(--orange)); 
            transition: width .3s ease; 
        }

        .steps { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin: 24px auto 30px; 
            max-width: 850px;
            padding: 0 10px;
        }
        .step { 
            flex: 1; 
            text-align: center; 
            position: relative; 
            cursor: pointer;
        }
        .bubble {
            width: 55px; 
            height: 55px; 
            border-radius: 50%; 
            background: #fff; 
            border: 2px solid #e2ecff;
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(15,23,42,0.08); 
            transition: all 0.3s;
            font-size: 16px;
            color: var(--text-muted);
        }
        .step .label { 
            margin-top: 8px; 
            color: var(--text-muted); 
            font-size: 13px; 
            font-weight: 500;
        }

        /* Active State */
        .step.active .bubble { 
            transform: translateY(-10px); 
            background: linear-gradient(180deg, var(--orange), var(--orange-dark)); 
            color: #fff; 
            border-color: transparent; 
            box-shadow: 0 15px 35px rgba(243, 112, 33, 0.3);
        }
        .step.active .label { color: var(--orange); font-weight: 700; }

        /* Done State */
        .step.done .bubble { 
            background: var(--success); color: #fff; border-color: transparent; transform: translateY(-6px); 
        }
        .step.done .label { color: var(--success); }

        /* Form Card */
        .form-card {
            background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden; min-height: 500px; display: flex; flex-direction: column;
        }

        .step-content { display: none; flex: 1; padding-bottom: 20px; }
        .step-content.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* Headers */
        .section-header { padding: 25px 30px; color: white; margin-bottom: 25px; }
        .bg-orange { background-color: var(--orange); }
        .bg-red { background: linear-gradient(90deg, #ff4b1f, #ff9068); }
        .bg-pink { background: linear-gradient(90deg, #e91e63, #f06292); }

        .section-header h3 { margin: 0; font-size: 1.4rem; }
        .section-header p { margin: 5px 0 0; opacity: 0.9; font-size: 0.95rem; }

        /* Form Fields */
        .form-body { padding: 0 30px; }
        .row { display: flex; gap: 20px; margin-bottom: 15px; }
        .col { flex: 1; }
        
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: #444; }
        input, select, textarea {
            width: 100%; padding: 12px; border: 1px solid #e0e0e0;
            background: #f8f9fa; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        input:focus, select:focus { border-color: var(--orange); outline: none; background: #fff; }

        /* Feature Styles Updated */
        .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; }
        
        /* Container for checkbox + input */
        .feature-item {
            display: flex; flex-direction: column;
        }
        
        .feature-box {
            border: 1px solid #e0e0e0; border-radius: 6px; padding: 12px;
            display: flex; align-items: center; cursor: pointer; transition: 0.2s;
            background: #f8f9fa;
        }
        .feature-box:hover { border-color: var(--orange); background: #fff8f0; }
        .feature-box input { width: auto; margin-right: 10px; }

        /* The hidden detail input */
        .detail-input {
            margin-top: 8px;
            display: none; /* Hidden by default */
            font-size: 13px;
            padding: 8px;
            border-color: #f37021;
            background: #fff;
            animation: slideDown 0.2s ease-out;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }

        /* Info/Tip Boxes */
        .info-box {
            margin: 30px 30px 10px; padding: 15px; border-radius: 8px; font-size: 0.9rem;
        }
        .info-orange { background: #fff3e0; border: 1px solid #ffe0b2; color: #e65100; }
        .info-red { background: #ffebee; border: 1px solid #ffcdd2; color: #c62828; }

        /* Footer Navigation */
        .form-footer {
            padding: 20px 30px; display: flex; justify-content: space-between;
            border-top: 1px solid #eee; margin-top: auto; background: #fff;
        }
        .btn {
            padding: 12px 25px; border-radius: 6px; border: none; font-size: 1rem; cursor: pointer; font-weight: 600;
        }
        .btn-prev { background: #fff; border: 1px solid #ddd; color: #666; width: 150px; }
        .btn-next { background: var(--orange); color: #fff; width: 200px; }
        .btn-submit { background: var(--pink); color: #fff; width: 200px; display: none; }
        .btn:hover { opacity: 0.9; }

        /* Back Button */
        .back-home-btn {
            position: absolute; top: 20px; left: 190px;
            background: #fff; border: 1px solid #ddd; padding: 10px 20px;
            border-radius: 30px; cursor: pointer; font-weight: 600; color: var(--text-dark);
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
            transition: all 0.3s ease; z-index: 100;
        }
        .back-home-btn:hover {
            background: #fff8f0; border-color: var(--orange); color: var(--orange); transform: translateX(-3px);
        }

        /* Signature Section */
        .signature-box {
            display: none; background: #fff0f0; border: 1px solid #ffcdd2; padding: 15px;
            border-radius: 8px; margin-top: 15px; animation: fadeIn 0.3s ease-in-out;
        }
        .signature-label { color: #c62828; font-weight: bold; display: block; margin-bottom: 8px; font-size: 0.95rem; }
        .signature-note { font-size: 0.85rem; color: #c62828; margin-top: 5px; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000;
        }
        .modal-content {
            background: #fff; width: 90%; max-width: 600px; padding: 30px; border-radius: 10px; max-height: 80vh; overflow-y: auto;
        }
        .tc-list { text-align: left; padding-left: 0; }
        .tc-list h4 { margin: 15px 0 5px; color: #333; font-size: 1rem; }
        .tc-list p { margin: 0; font-size: 0.9rem; color: #555; line-height: 1.5; }
        .tc-contact-box {
            background: #fff5f5; border: 1px solid #ffcdd2; padding: 15px; border-radius: 8px; margin-top: 20px; color: #c62828; font-size: 0.9rem;
        }

        /* Success Step */
        .success-step { text-align: center; padding: 50px 30px; }
        .success-icon {
            width: 80px; height: 80px; background: #fff3e0; color: var(--orange);
            font-size: 40px; border-radius: 50%; display: inline-flex; align-items: center;
            justify-content: center; margin-bottom: 20px;
        }
        .success-list { text-align: left; background: #fff8f0; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success-list li { margin-bottom: 8px; color: #555; }
        
        /* New Tracking ID Box */
        .tracking-box {
            background: #e0f2fe;
            border: 2px dashed #0ea5e9;
            color: #0284c7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .tracking-code {
            display: block;
            font-size: 1.4rem;
            font-family: monospace;
            margin-top: 5px;
            letter-spacing: 1px;
            color: #000;
        }

        @media (max-width: 600px) {
            .back-home-btn { position: static; margin: 10px 0 0 20px; display: inline-flex; }
            .steps { display: none; } 
            .top-progress { display:block; }
            .feature-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<button class="back-home-btn" type="button" onclick="window.location.href='index.php'">
    <span style="font-size:16px; margin-right:4px;">←</span> Back to Home
</button>

<div class="main-container">
    <div class="top-header">
        <h4>Request an agent</h4>
        <p style="color:#666;">Tell us what you're looking for and our expert agents will help you find the perfect property</p>
        <button class="browse-btn" type="button" onclick="window.location.href='professionals.php'">
            <i class="fas fa-eye"></i> Search Available Properties
        </button>
    </div>

    <div class="top-progress" role="progressbar">
        <div id="progressFill" class="fill" style="width:0%"></div>
    </div>

    <div class="steps">
        <div class="step active" id="p-step1" onclick="changeStepTo(1)"><div class="bubble">1</div><div class="label">Personal</div></div>
        <div class="step" id="p-step2" onclick="changeStepTo(2)"><div class="bubble">2</div><div class="label">Preferences</div></div>
        <div class="step" id="p-step3" onclick="changeStepTo(3)"><div class="bubble">3</div><div class="label">Location</div></div>
        <div class="step" id="p-step4" onclick="changeStepTo(4)"><div class="bubble">4</div><div class="label">Financing</div></div>
        <div class="step" id="p-step5" onclick="changeStepTo(5)"><div class="bubble">5</div><div class="label">Finalize</div></div>
    </div>

    <form id="mainForm" method="POST" action="">
    <div class="form-card">
        
        <div class="step-content active" id="step1">
            <div class="section-header bg-orange">
                <div style="display:flex; gap:15px; align-items:center;">
                    <i class="fas fa-user fa-2x"></i>
                    <div><h3>Your Information</h3><p>How can we reach you with property matches?</p></div>
                </div>
            </div>
            <div class="form-body">
                <div class="row">
                    <div class="col"><label>First Name *</label><input type="text" name="first_name" required></div>
                    <div class="col"><label>Last Name *</label><input type="text" name="last_name" required></div>
                </div>
                <div class="row">
                    <div class="col"><label>Email Address *</label><input type="email" name="email" required></div>
                </div>
                <div class="row">
                    <div class="col"><label>Phone Number *</label><input type="text" name="phone" required></div>
                    <div class="col">
                        <label>Preferred Contact Method</label>
                        <select name="contact_method" required>
                            <option value="" disabled selected>Select Method</option>
                            <option>Email</option>
                            <option>Phone Call</option>
                            <option>Text Message</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="info-box info-orange"><strong>Privacy:</strong> Your information will only be used to send you property recommendations and updates.</div>
        </div>

        <div class="step-content" id="step2">
            <div class="section-header bg-orange">
                <div style="display:flex; gap:15px; align-items:center;">
                    <i class="fas fa-home fa-2x"></i>
                    <div><h3>Property Preferences</h3><p>What type of home are you looking for?</p></div>
                </div>
            </div>
            <div class="form-body">
                <div class="row">
                    <div class="col">
                        <label>Property Type</label>
                        <select name="property_type" required>
                            <option value="" disabled selected>Select Type</option>
                            <option>Single Family Home</option>
                            <option>Townhouse</option>
                            <option>Condominium</option>
                            <option>Villa</option>
                            <option>Any Type</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label>Min Price</label>
                        <select name="min_price" required>
                            <option value="" disabled selected>Select</option>
                            <option value="200000">$200,000</option>
                            <option value="300000">$300,000</option>
                            <option value="400000">$400,000</option>
                            <option value="500000">$500,000</option>
                            <option value="600000">$600,000</option>
                        </select>
                    </div>
                    <div class="col">
                        <label>Max Price</label>
                        <select name="max_price" required>
                            <option value="" disabled selected>Select</option>
                            <option value="400000">$400,000</option>
                            <option value="500000">$500,000</option>
                            <option value="600000">$600,000</option>
                            <option value="700000">$700,000</option>
                            <option value="800000">$800,000</option>
                            <option value="1000000">$1,000,000+</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label>Min Bedrooms</label>
                        <select name="min_bedrooms" required>
                            <option value="" disabled selected>Select</option>
                            <option>1+</option>
                            <option>2+</option>
                            <option>3+</option>
                            <option>4+</option>
                            <option>5+</option>
                        </select>
                    </div>
                    <div class="col">
                        <label>Min Bathrooms</label>
                        <select name="min_bathrooms" required>
                            <option value="" disabled selected>Select</option>
                            <option>1+</option>
                            <option>2+</option>
                            <option>3+</option>
                            <option>4+</option>
                        </select>
                    </div>
                    <div class="col"><label>Min Sq Ft</label><input type="number" name="min_sq_feet" placeholder="e.g. 2000"></div>
                </div>
                
                <label style="margin-top:10px;">Must-Have Features</label>
                <div class="feature-grid">
                    <div class="feature-item">
                        <label class="feature-box">
                            <input type="checkbox" name="feat_garage" value="Garage" onchange="toggleDetail('garage')"> Garage
                        </label>
                        <input type="number" id="detail_garage" name="detail_garage" class="detail-input" placeholder="How many garage? (e.g., 2)">
                    </div>

                    <div class="feature-item">
                        <label class="feature-box">
                            <input type="checkbox" name="feat_basement" value="Basement" onchange="toggleDetail('basement')"> Basement size
                        </label>
                        <input type="text" id="detail_basement" name="detail_basement" class="detail-input" placeholder="Full/Finished?">
                    </div>

                    <div class="feature-item">
                        <label class="feature-box">
                            <input type="checkbox" name="feat_kitchen" value="Updated Kitchen" onchange="toggleDetail('kitchen')"> Updated Kitchen
                        </label>
                        <input type="text" id="detail_kitchen" name="detail_kitchen" class="detail-input" placeholder="Specific requirements?">
                    </div>

                    <div class="feature-item">
                        <label class="feature-box">
                            <input type="checkbox" name="feat_backyard" value="Backyard" onchange="toggleDetail('backyard')"> Backyard
                        </label>
                        <input type="text" id="detail_backyard" name="detail_backyard" class="detail-input" placeholder="Size/Fenced?">
                    </div>
                </div>

            </div>
            <div class="info-box info-orange"><strong>Tip:</strong> Don't worry if you're not sure about all preferences - our agents will help refine your search!</div>
        </div>

        <div class="step-content" id="step3">
            <div class="section-header bg-orange">
                <div style="display:flex; gap:15px; align-items:center;">
                    <i class="fas fa-map-marker-alt fa-2x"></i>
                    <div><h3>Location & Details</h3><p>Where would you like to live?</p></div>
                </div>
            </div>
            <div class="form-body">
                <label>Preferred Location(s) *</label>
                <input type="text" name="preferred_locations" placeholder="e.g. Manassas Park, Fairfax, Arlington" style="margin-bottom:20px;">
                <label>Additional Requirements</label>
                <textarea name="additional_reqs" rows="5" placeholder="Tell us about specific features, school districts, etc."></textarea>
            </div>
            <div class="info-box info-orange"><strong>Local Expertise:</strong> Our agents have deep knowledge of neighborhood amenities, schools, and market trends.</div>
        </div>

        <div class="step-content" id="step4">
            <div class="section-header bg-red">
                <div style="display:flex; gap:15px; align-items:center;">
                    <i class="fas fa-dollar-sign fa-2x"></i>
                    <div><h3>Financing & Timeline</h3><p>Help us understand your buying timeline</p></div>
                </div>
            </div>
            <div class="form-body">
                <div class="row">
                    <div class="col">
                        <label>Pre-approved for mortgage?</label>
                        <select name="pre_approved" required>
                            <option value="" disabled selected>Select</option>
                            <option>Yes, pre-approved</option>
                            <option>In progress</option>
                            <option>Not yet</option>
                        </select>
                    </div>
                    <div class="col">
                        <label>First-time home buyer?</label>
                        <select name="first_time_buyer" required>
                            <option value="" disabled selected>Select</option>
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                    </div>
                </div>
                <label>When are you looking to buy?</label>
                <select name="buy_timeline" required>
                    <option value="" disabled selected>Select timeframe</option>
                    <option>Immediately/ASAP</option>
                    <option>1-3 Months</option>
                    <option>3-6 Months</option>
                    <option>6-12 Months</option>
                    <option>Just browsing</option>
                </select>
            </div>
            <div class="info-box info-orange">
                <h4 style="margin:0 0 5px; color:#d35400;">Need financing help?</h4>
                We can connect you with trusted loan officers who specialize in helping buyers secure the best mortgage rates.
            </div>
        </div>

        <div class="step-content" id="step5">
            <div class="section-header bg-pink">
                <div style="display:flex; gap:15px; align-items:center;">
                    <i class="fas fa-check-circle fa-2x"></i>
                    <div><h3>Finalize Request</h3><p>Review and submit your property search</p></div>
                </div>
            </div>
            <div class="form-body">
                <div style="background:#fff; border:1px solid #e0e0e0; padding:20px; border-radius:8px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                    
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <input type="checkbox" id="tcCheck" name="agreed_to_terms" onchange="toggleSignature()" style="width:20px; height:20px; margin-top:3px; cursor:pointer;">
                        <div style="font-size:0.95rem; line-height:1.5; color:#333;">
                            I agree to the <a href="javascript:void(0)" onclick="openModal()" style="color:var(--red); text-decoration:underline; font-weight:bold;">Terms and Conditions</a> and confirm that the information provided is accurate. I would like to receive property recommendations matching my criteria.
                        </div>
                    </div>

                    <div id="signatureSection" class="signature-box">
                        <label class="signature-label"><i class="fas fa-pen-nib"></i> Your Signature *</label>
                        <input type="text" id="signatureInput" name="digital_signature" placeholder="Type your full name as signature">
                        <p class="signature-note">By typing your name above, you are electronically signing this request form.</p>
                    </div>

                </div>
            </div>
            <div class="info-box info-red">
                <strong>Ready to find your dream home?</strong> Submit your request and our team will start searching for the perfect property for you!
            </div>
        </div>

        <div class="form-footer">
            <button type="button" class="btn btn-prev" id="prevBtn" onclick="changeStep(-1)">← Previous</button>
            <button type="button" class="btn btn-next" id="nextBtn" onclick="changeStep(1)">Next Step →</button>
            <button type="submit" class="btn btn-submit" id="submitBtn">Submit Request</button>
        </div>

    </div>
    </form>
</div>

<div class="modal-overlay" id="tcModal">
    <div class="modal-content">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:15px;">Property Search Terms</h3>
        
        <div style="max-height: 60vh; overflow-y: auto; padding-right:10px;">
            <div class="tc-list">
                <h4>1. Information Accuracy</h4>
                <p>You confirm that all information provided in this request is accurate and complete to the best of your knowledge.</p>

                <h4>2. Contact Authorization</h4>
                <p>By submitting this form, you authorize DreamHome Builders and our real estate agents to contact you via your preferred method regarding property recommendations and home buying services.</p>

                <h4>3. Property Matching</h4>
                <p>We will use your preferences to send you curated property listings. We typically respond within 24 hours with initial matches.</p>

                <h4>4. No Obligation</h4>
                <p>This request does not obligate you to purchase any property or work exclusively with our agents. You are free to explore other options at any time.</p>

                <h4>5. Privacy</h4>
                <p>Your personal information will be used solely for the purpose of helping you find a home and will not be shared with third parties without your consent.</p>

                <h4>6. Market Information</h4>
                <p>Property prices, availability, and details are subject to change. We will provide the most current information available at the time of communication.</p>
            </div>

            <div class="tc-contact-box">
                <strong>Questions?</strong> Contact our team at any time if you have questions about these terms or our services.
            </div>
        </div>

        <button class="btn btn-prev" style="width:100%; margin-top:20px; background:#111; color:#fff; border:none;" onclick="closeModal()">Close</button>
    </div>
</div>

<?php if($success): ?>
<div class="modal-overlay" style="display:flex;">
    <div class="modal-content success-step">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h2>Request Submitted!</h2>
        <p>Thank you for choosing DreamHome Builders. Our team will review your preferences and contact you within <strong>24 hours</strong>.</p>
        
        <div class="tracking-box">
            Your Tracking ID:
            <span class="tracking-code"><?php echo htmlspecialchars($tracking_id); ?></span>
        </div>

        <div class="success-list">
            <strong>What's next:</strong>
            <ul>
                <li>Curated property listings matching your criteria</li>
                <li>Our buyer's agent will reach out to you</li>
                <li>Expert guidance throughout the process</li>
            </ul>
        </div>
        
        <div style="display:flex; gap:10px; flex-direction:column;">
            <button class="btn btn-next" style="width:100%; background:#0284c7;" onclick="window.location.href='index.php'">Submit Another Request</button>
            <button class="btn btn-prev" style="width:100%" onclick="window.location.href='index.php'">Return to Home</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    let currentStep = 1;
    const totalSteps = 5;

    function init() {
        showStep(currentStep);
    }

    function showStep(step) {
        document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
        document.getElementById('step' + step).classList.add('active');

        // Progress Calculation
        const percent = ((step - 1) / (totalSteps - 1)) * 100;
        document.getElementById('progressFill').style.width = percent + '%';

        // BUBBLE UPDATE LOGIC
        for (let i = 1; i <= totalSteps; i++) {
            const item = document.getElementById('p-step' + i);
            item.classList.remove('active', 'done'); 
            
            if (i < step) {
                item.classList.add('done');
            } else if (i === step) {
                item.classList.add('active');
            }
        }

        // Buttons
        document.getElementById('prevBtn').style.visibility = step === 1 ? 'hidden' : 'visible';
        if (step === totalSteps) {
            document.getElementById('nextBtn').style.display = 'none';
            document.getElementById('submitBtn').style.display = 'block';
        } else {
            document.getElementById('nextBtn').style.display = 'block';
            document.getElementById('submitBtn').style.display = 'none';
        }
    }

    // Toggle logic for Feature Details
    function toggleDetail(feature) {
        const input = document.getElementById('detail_' + feature);
        if (input.style.display === 'block') {
            input.style.display = 'none';
            input.value = ''; // Clear if unchecked
        } else {
            input.style.display = 'block';
            input.focus();
        }
    }

    // New function to allow clicking bubbles to navigate back
    function changeStepTo(n) {
        if (n < currentStep) {
            currentStep = n;
            showStep(currentStep);
        }
    }

    function changeStep(n) {
        if (n === 1 && !validateForm()) return; 
        currentStep += n;
        showStep(currentStep);
    }

    function validateForm() {
        const activeStep = document.getElementById('step' + currentStep);
        const inputs = activeStep.querySelectorAll('input[required], select[required]');
        let valid = true;
        inputs.forEach(input => {
            // Check if value is empty or specifically "Select" (just in case)
            if (!input.value || input.value === "Select") {
                input.style.border = "1px solid red";
                valid = false;
            } else {
                input.style.border = "1px solid #e0e0e0";
            }
        });
        return valid;
    }

    // Modal
    function openModal() { document.getElementById('tcModal').style.display = 'flex'; }
    function closeModal() { document.getElementById('tcModal').style.display = 'none'; }

    // Toggle Signature
    function toggleSignature() {
        const checkbox = document.getElementById('tcCheck');
        const signatureSection = document.getElementById('signatureSection');
        const signatureInput = document.getElementById('signatureInput');

        if (checkbox.checked) {
            signatureSection.style.display = 'block';
            signatureInput.required = true;
        } else {
            signatureSection.style.display = 'none';
            signatureInput.required = false;
            signatureInput.value = ''; // Optional: clear value
        }
    }

    init();
</script>
</body>
</html>