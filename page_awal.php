<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_guard.php'; 
require_once 'koneksi.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Ujikom | Instansi Pembina JF</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.4);
            --dark-bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.6);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--dark-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content-wrapper {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background: 
                radial-gradient(circle at 15% 25%, rgba(99, 102, 241, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 75%, rgba(16, 185, 129, 0.08) 0%, transparent 45%),
                #0f172a;
            z-index: 1;
        }

        /* Ambient Background Orbs */
        .orb {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            z-index: -1;
            opacity: 0.3;
            animation: float 25s infinite alternate ease-in-out;
        }
        .orb-1 { top: -150px; left: -150px; background: var(--primary); }
        .orb-2 { bottom: -150px; right: -150px; background: var(--success); }

        .hero-section {
            text-align: center;
            margin-bottom: 70px;
            animation: fadeInDown 1s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .hero-section h1 {
            font-weight: 800;
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            letter-spacing: -0.04em;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 30%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        .hero-section p {
            color: var(--text-muted);
            font-size: clamp(1rem, 2vw, 1.25rem);
            max-width: 650px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .card-container {
            display: flex;
            gap: 30px;
            max-width: 1000px;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .selection-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 32px;
            padding: 50px 40px;
            flex: 1;
            min-width: 320px;
            max-width: 420px;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
        }

        .selection-card:hover {
            transform: translateY(-12px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.5);
        }

        .icon-box {
            width: 88px;
            height: 88px;
            margin: 0 auto 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            font-size: 36px;
            color: white;
            transition: all 0.4s ease;
        }

        .card-perpindahan .icon-box {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            box-shadow: 0 12px 24px -6px var(--primary-glow);
        }

        .card-kenaikan .icon-box {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 12px 24px -6px var(--success-glow);
        }

        .selection-card:hover .icon-box {
            transform: scale(1.1) rotate(8deg);
        }

        .selection-card h3 {
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }

        .selection-card p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 40px;
            font-size: 1rem;
            flex-grow: 1;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 24px;
            border-radius: 20px;
            font-weight: 700;
            text-decoration: none !important;
            transition: all 0.3s ease;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.85rem;
        }

        .btn-perpindahan { 
            background: var(--primary); 
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .btn-kenaikan { 
            background: var(--success); 
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-action:hover {
            filter: brightness(1.1);
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .btn-action i {
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: translateX(5px);
        }

        /* Footer Style */
        .footer {
            padding: 30px 20px;
            text-align: center;
            background: rgba(15, 23, 42, 0.8);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 2;
        }

        .footer p {
            color: #64748b;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
        }

        /* Animations */
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, 60px) scale(1.1); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-1 { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s backwards; }
        .card-2 { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.4s backwards; }

        @media (max-width: 768px) {
            .hero-section { margin-bottom: 40px; }
            .selection-card { padding: 40px 30px; }
            .card-container { gap: 20px; }
        }
    </style>
</head>
<body>

<div class="content-wrapper">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="hero-section">
        <h1>Layanan Ujikom</h1>
        <p>Sistem Informasi Jabatan Fungsional. Silakan pilih kategori pengajuan untuk memulai proses administrasi Anda.</p>
    </div>

    <div class="card-container">
        <div class="selection-card card-1 card-perpindahan">
            <div class="icon-box">
                <i class="fas fa-right-left"></i>
            </div>
            <h3>Perpindahan Jabatan</h3>
            <p>Proses verifikasi pengajuan perpindahan antar rumpun jabatan fungsional dalam lingkup instansi.</p>
            <a href="index_pengusul.php?hide_kenaikan=1" class="btn-action btn-perpindahan">
                Masuk Layanan <i class="fas fa-arrow-right-long"></i>
            </a>
        </div>

        <div class="selection-card card-2 card-kenaikan">
            <div class="icon-box">
                <i class="fas fa-arrow-trend-up"></i>
            </div>
            <h3>Kenaikan Jabatan</h3>
            <p>Manajemen pengajuan kenaikan jenjang jabatan fungsional untuk pengembangan karir yang berkelanjutan.</p>
            <a href="index_pengusul.php?hide_perpindahan=1" class="btn-action btn-kenaikan">
                Masuk Layanan <i class="fas fa-arrow-right-long"></i>
            </a>
        </div>
    </div>
</div>

<footer class="footer">
    <p>Copyright &copy; 2026 <strong>Instansi Pembina JF</strong>. All rights reserved.</p>
</footer>

</body>
</html>