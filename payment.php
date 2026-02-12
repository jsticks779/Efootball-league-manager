<?php
session_start();
require 'db.php';
if (!isset($_GET['match_id'])) { header("Location: index.php"); exit; }
$match_id = intval($_GET['match_id']);
$uid = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
        .error-msg { color: #ff3333; font-size: 0.9rem; margin-top: 5px; display: none; }
        .spinner { border: 4px solid rgba(255,255,255,0.1); border-top: 4px solid var(--accent); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div style="position: absolute; top: 20px; right: 20px; z-index: 10;">
        <button class="theme-toggle" onclick="toggleTheme()" title="Switch Theme">
            <i class="fas fa-sun"></i>
        </button>
    </div>
    <div class="container" style="display:flex; justify-content:center; align-items:center; min-height:80vh;">
        <div class="stat-card" style="width:100%; max-width:450px; text-align:left;">
            <h2 style="text-align:center; color:var(--accent); margin-bottom:20px;">PAYMENT GATEWAY</h2>
            
            <div id="step1">
                <label style="color:#888;">Select Network</label>
                <div class="network-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:15px;">
                    <div class="btn btn-outline" onclick="selectNet(this, 'voda')" style="justify-content:center;">M-Pesa</div>
                    <div class="btn btn-outline" onclick="selectNet(this, 'tigo')" style="justify-content:center;">Tigo</div>
                    <div class="btn btn-outline" onclick="selectNet(this, 'airtel')" style="justify-content:center;">Airtel</div>
                    <div class="btn btn-outline" onclick="selectNet(this, 'halo')" style="justify-content:center;">Halotel</div>
                </div>
                <input type="hidden" id="selectedNet" value="">

                <label style="color:#888;">Phone Number</label>
                <input type="text" id="phone" placeholder="07..." style="width:100%; padding:12px; background:#222; border:1px solid #444; color:white; border-radius:8px;">
                <div id="error" class="error-msg">Invalid number for selected network.</div>

                <button class="btn btn-primary" onclick="validateAndPay()" style="width:100%; margin-top:20px; justify-content:center;">INITIATE PAYMENT</button>
            </div>

            <div id="step2" style="display:none; text-align:center;">
                <div class="spinner"></div>
                <h3>Sending Request...</h3>
                <p style="color:#aaa;">Check your phone for the PIN popup.</p>
            </div>

            <div id="step3" style="display:none;">
                <div style="background:rgba(0,229,255,0.1); border:1px solid var(--accent); padding:15px; border-radius:8px; text-align:center; margin-bottom:20px;">
                    <p style="margin:0; font-size:0.9rem;">Send <strong>200/=</strong> to:</p>
                    <h2 style="margin:5px 0;">0621 344 755</h2>
                    <p style="margin:0; font-size:0.8rem;">JOYCE RICHARD BACHUBILA</p>
                </div>
                <form action="verify_payment.php" method="POST">
                    <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                    <label>Transaction ID (from SMS) or  SENDER NAME</label>
                    <input type="text" name="trans_id" required style="width:100%; padding:12px; background:#222; border:1px solid #444; color:white; border-radius:8px; margin-bottom:15px; text-transform:uppercase;">
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">CONFIRM PAYMENT</button>
                </form>
            </div>

        </div>
    </div>

    <script>
        function selectNet(el, net) {
            document.querySelectorAll('.btn-outline').forEach(b => { b.style.borderColor = '#444'; b.style.color = '#fff'; });
            el.style.borderColor = 'var(--accent)';
            el.style.color = 'var(--accent)';
            document.getElementById('selectedNet').value = net;
        }

        function validateAndPay() {
            const net = document.getElementById('selectedNet').value;
            const phone = document.getElementById('phone').value;
            const err = document.getElementById('error');
            
            if(!net) { err.innerText = "Please select a network."; err.style.display = 'block'; return; }
            
            // Regex for TZ Networks
            const patterns = {
                'voda': /^(074|075|076)/,
                'tigo': /^(071|065|067)/,
                'airtel': /^(078|068|069)/,
                'halo': /^(062)/
            };

            if (!phone.match(/^\d{10}$/)) {
                err.innerText = "Phone must be 10 digits."; err.style.display = 'block'; return;
            }
            if (!patterns[net].test(phone)) {
                err.innerText = "Number does not match selected network prefix."; err.style.display = 'block'; return;
            }

            // Validation Passed
            err.style.display = 'none';
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';

            // Simulate API Delay
            setTimeout(() => {
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
            }, 4000);
        }
    </script>
    <script src="theme.js"></script>
</body>
</html>
