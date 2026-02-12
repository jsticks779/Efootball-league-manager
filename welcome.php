<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | E-League</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        
body, html { 
    background-color: #000; 
}

/* Make sure the video covers the screen smoothly */
.video-bg {
    /* ... keep your existing settings ... */
    transition: opacity 0.5s ease-in-out;
}
        /* 1. Full Screen Hero Section */
        body, html { height: 100%; overflow-x: hidden; }
        
        .hero-section {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            /* Fallback background if video fails */
            background: radial-gradient(circle at center, #1a1a2e 0%, #000 100%);
        }

        /* 2. Background Video/Image Container */
        .video-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            z-index: 0;
            opacity: 0.4; /* Dim it so text pops */
            filter: blur(2px); /* Cinematic blur */
        }

        /* Overlay Grid Pattern */
        .overlay-grid {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(0, 229, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 229, 255, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
        }

        /* 3. Main Content Content */
        .content-box {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            padding: 20px;
        }

        /* Animated PC/Football Image Container */
        .hero-visual {
            width: 280px;
            height: 280px; /* Adjust based on your image */
            margin: 0 auto 30px auto;
            position: relative;
            animation: float 6s ease-in-out infinite;
        }
        
        .hero-visual img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 20px var(--accent-glow));
        }

        /* Typography Animations */
        .main-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0;
            animation: slideUp 0.8s ease-out forwards 0.5s;
        }
        
        .main-title span { color: var(--accent); text-shadow: 0 0 20px var(--accent); }

        .sub-text {
            font-size: 1.2rem;
            color: #ccc;
            margin-bottom: 40px;
            opacity: 0;
            animation: slideUp 0.8s ease-out forwards 0.8s;
        }

        /* Action Buttons */
        .btn-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            opacity: 0;
            animation: fadeIn 1s ease-out forwards 1.2s;
        }

        .btn-big {
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-glow {
            background: var(--accent);
            color: #000;
            box-shadow: 0 0 20px var(--accent-glow);
        }
        .btn-glow:hover {
            transform: scale(1.05);
            box-shadow: 0 0 40px var(--accent-glow);
        }

        .btn-glass {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            backdrop-filter: blur(5px);
        }
        .btn-glass:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
        }

        /* --- ANIMATIONS --- */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .main-title { font-size: 2.5rem; }
            .hero-visual { width: 200px; height: 200px; }
            .btn-group { flex-direction: column; width: 100%; max-width: 300px; margin: 0 auto; }
            .btn-big { width: 100%; }
        }
    </style>
</head>
<body>

    <section class="hero-section">
        
       <video autoplay muted loop playsinline class="video-bg">
    <source src="uploads/WhatsApp Video 2026-02-09 at 02.13.46.mp4" type="video/mp4">
</video>
        
        <div class="overlay-grid"></div>

        <div class="content-box">
            
            <div class="hero-visual">
                <img src="https://cdn-icons-png.flaticon.com/512/3408/3408506.png" alt="Gamer PC">
            </div>

            <h1 class="main-title">E-FOOTBALL <span>LEAGUE</span></h1>
            <p class="sub-text">Join the Ultimate Online Tournament. Compete. Win. Get paid.</p>

            <div class="btn-group">
                <a href="login.php" class="btn-big btn-glow">
                    <i class="fas fa-play"></i> Login
                </a>
                <a href="register.php" class="btn-big btn-glass">
                    <i class="fas fa-user-plus"></i> Join Now
                </a>
            </div>

        </div>

        <div style="position:absolute; bottom:30px; left:30px; color:#666; font-size:0.8rem; z-index:2;">
            <i class="fas fa-circle" style="color:var(--success); font-size:0.6rem;"></i> SERVER ONLINE
        </div>

    </section>

</body>
</html>