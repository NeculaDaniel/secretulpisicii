<?php
// Includem config.php pentru a avea acces la variabilele din .env
require_once __DIR__ . '/config.php';

// Verificăm dacă cheia există
$googleApiKey = defined('GOOGLE_PLACES_API_KEY') ? GOOGLE_PLACES_API_KEY : '';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <title>Secretul Pisicii - Perie Profesională cu Tehnologie Nano-Steam</title>
    <meta name="description" content="Scapă de părul de pisică de pe haine și protejează sănătatea familiei! Peria cu aburi Nano-Steam oferă un masaj relaxant.">
    <meta name="keywords" content="perie pisici, perie cu aburi, nano-steam, îngrijire pisici, păr pisică, perie profesională">
    <meta name="author" content="Secretul Pisicii - ALTMAR GROUP S.R.L.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://secretulpisicii.alvoro.ro/">
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://secretulpisicii.alvoro.ro/">
    <meta property="og:title" content="Secretul Pisicii - Perie Profesională cu Tehnologie Nano-Steam">
    <meta property="og:description" content="Scapă de părul de pisică de pe haine și protejează sănătatea familiei! Peria cu aburi Nano-Steam oferă un masaj relaxant.">
    <meta property="og:image" content="https://secretulpisicii.alvoro.ro/assets/og-image.jpg">
    
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    <link rel="stylesheet" href="https://widget.bliskapaczka.pl/v7/main.css" />

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XG6BPSTJVM"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XG6BPSTJVM');
    </script>
    
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '534315312988087');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=534315312988087&ev=PageView&noscript=1"/>
    </noscript>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* 1. CSS VARIABLES & RESET */
        :root {
            --primary-color: #eb2571;
            --primary-hover: #c91e57;
            --accent-color: #f59e0b;
            --text-main: #1f2937;
            --text-light: #6b7280;
            --bg-light: #fbfaf9;
            --bg-white: #ffffff;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            
            --container-width: min(90vw, 1100px);
            --radius-md: clamp(8px, 1.2vw, 12px);
            --radius-sm: clamp(6px, 0.8vw, 8px);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-text-size-adjust: 100%; }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            background-color: var(--bg-white);
            line-height: 1.6;
            padding-top: clamp(70px, 10vh, 90px);
            text-align: left;
        }
        
        p, li, span, div { text-align: left; word-spacing: normal; }

        .section-title, .section-subtitle, .hero-text, .reviews-header, #offer, #video-demo { text-align: center; }
        .hero-text h1, .hero-tagline { text-align: center; }

        @media(min-width: 768px) {
            .hero-text { text-align: left; align-items: flex-start; }
            .hero-text h1, .hero-tagline { text-align: left; }
        }

        img, video { max-width: 100%; height: auto; display: block; }
        ul { list-style: none; }
        a { text-decoration: none; color: inherit; }

        /* 2. UTILITY CLASSES */
        .container {
            width: 100%; max-width: var(--container-width); margin: 0 auto; padding: 0 clamp(1rem, 3vw, 1.25rem);
        }
        
        .section-title {
            font-size: clamp(1.5rem, 4vw, 2.5rem); font-weight: 800; margin-bottom: clamp(0.5rem, 1.5vh, 1rem); line-height: 1.2; color: var(--text-main);
        }

        .section-subtitle {
            color: var(--text-light); max-width: 600px; margin: 0 auto clamp(1.5rem, 3vh, 2rem) auto; font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        .btn {
            display: inline-flex; justify-content: center; align-items: center;
            padding: clamp(10px, 1.5vh, 12px) clamp(20px, 3vw, 24px);
            font-weight: 600; font-size: clamp(0.9rem, 2vw, 1rem);
            border-radius: 50px; transition: all 0.3s ease; cursor: pointer; border: none;
            text-align: center; box-shadow: var(--shadow-md); touch-action: manipulation;
            min-height: clamp(44px, 6vh, 50px);
        }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); transform: translateY(-2px); }
        .btn-secondary { background-color: white; color: var(--text-main); border: 2px solid var(--border-color); }
        .btn-large { width: 100%; font-size: clamp(1rem, 2.2vw, 1.1rem); padding: clamp(12px, 2vh, 14px) clamp(24px, 4vw, 28px); }
        @media(min-width: 768px) { .btn-large { width: auto; min-width: clamp(250px, 30vw, 300px); } }

        /* 3. HEADER */
        header {
            position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;
            background-color: rgba(255, 255, 255, 0.98); border-bottom: 1px solid var(--border-color);
            padding: clamp(0.9rem, 1.8vh, 1.1rem) 0; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-weight: 800; font-size: clamp(1.1rem, 2.8vw, 1.3rem); color: var(--text-main); cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .header-cta { padding: clamp(10px, 1.5vh, 12px) clamp(20px, 3vw, 24px); font-size: clamp(0.9rem, 2vw, 1rem); min-height: clamp(42px, 5.5vh, 48px); }
        
        /* 4. HERO SECTION */
        #hero { padding: clamp(1.5rem, 3vh, 3rem) 0; background: linear-gradient(to bottom, var(--bg-light), white); }
        .hero-grid { display: flex; flex-direction: column; gap: clamp(1.5rem, 3vh, 2rem); align-items: center; }
        @media(min-width: 768px) { .hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(2rem, 5vw, 4rem); padding: clamp(1rem, 3vh, 2rem) 0; } #hero { padding: clamp(2rem, 5vh, 4rem) 0; } }
        .hero-media { width: 100%; order: 1; }
        .hero-text { display: flex; flex-direction: column; order: 2; }
        @media(min-width: 768px) { .hero-media { order: 1; } .hero-text { order: 2; } }
        .hero-text h1 { font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.1; font-weight: 800; margin-bottom: clamp(0.75rem, 2vh, 1rem); width: 100%; }
        .hero-tagline { font-size: clamp(1rem, 2.5vw, 1.1rem); color: var(--text-light); margin-bottom: clamp(1rem, 2vh, 1.5rem); }
        .hero-benefits { margin-bottom: clamp(1rem, 2vh, 1.5rem); font-size: clamp(0.9rem, 2vw, 1rem); text-align: left; display: inline-block; }
        .hero-benefits li { margin-bottom: clamp(0.4rem, 1vh, 0.5rem); display: flex; align-items: flex-start; }
        .hero-benefits li::before { content: "✅"; margin-right: 10px; }
        .hero-actions { display: flex; flex-direction: column; gap: clamp(0.75rem, 1.5vh, 1rem); width: 100%; }
        @media(min-width: 480px) { .hero-actions { flex-direction: row; justify-content: center; } }
        @media(min-width: 768px) { .hero-actions { justify-content: flex-start; } }
        .media-placeholder { width: 100%; aspect-ratio: 4/4; border-radius: var(--radius-md); overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.15); background: #000; }
        .hero-video { width: 100%; height: 100%; object-fit: cover; }
        .trust-badges { margin-top: clamp(1rem, 2vh, 1.5rem); font-size: clamp(0.85rem, 1.8vw, 0.9rem); color: var(--text-light); display: flex; align-items: center; justify-content: center; gap: clamp(0.75rem, 2vw, 1rem); flex-wrap: wrap; }
        @media(min-width: 768px) { .trust-badges { justify-content: flex-start; } }

        /* 5. URGENCY BAR */
        #urgency { background-color: #fee2e2; border-top: 1px solid #fca5a5; border-bottom: 1px solid #fca5a5; padding: clamp(0.6rem, 1.2vh, 0.75rem) clamp(0.75rem, 2vw, 1rem); text-align: center; }
        .stock-alert { color: #b91c1c; font-weight: 800; font-size: clamp(0.9rem, 2vw, 1rem); display: flex; align-items: center; justify-content: center; gap: 10px; text-transform: uppercase; width: fit-content; margin: 0 auto; }
        .stock-dot { height: 12px; width: 12px; background-color: #dc2626; border-radius: 50%; animation: pulse-red 1s infinite; border: 2px solid #fff; flex-shrink: 0; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); transform: scale(0.95); } 70% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); transform: scale(1); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); transform: scale(0.95); } }

        /* 6. FEATURES */
        #features { padding: clamp(2rem, 4vh, 3rem) 0; background-color: var(--bg-light); }
        .features-grid { display: grid; grid-template-columns: 1fr; gap: clamp(1rem, 2vh, 1.5rem); }
        @media(min-width: 640px) { .features-grid { grid-template-columns: 1fr 1fr; } }
        @media(min-width: 1024px) { .features-grid { grid-template-columns: repeat(3, 1fr); } }
        .feature-card { background: white; padding: clamp(1.25rem, 2.5vh, 1.5rem); border-radius: var(--radius-md); box-shadow: var(--shadow-md); text-align: center; display: flex; flex-direction: column; align-items: center; }
        .feature-icon { font-size: clamp(1.75rem, 4vw, 2rem); margin-bottom: clamp(0.5rem, 1vh, 0.75rem); color: var(--primary-color); text-align: center; width: 100%; }
        .feature-title { font-weight: 700; margin-bottom: clamp(0.4rem, 0.8vh, 0.5rem); font-size: clamp(1rem, 2.2vw, 1.1rem); text-align: center; }
        .feature-card p { text-align: center; font-size: clamp(0.9rem, 1.8vw, 1rem); }

        /* 7. VIDEO DEMO */
        #video-demo { padding: clamp(2rem, 4vh, 3rem) 0; background: white; }
        #video-demo .container { max-width: min(90vw, 800px); }
        .video-container { width: 100%; aspect-ratio: 4/4; background: #000; border-radius: var(--radius-md); overflow: hidden; margin-top: clamp(1rem, 2vh, 1.5rem); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        @media(min-width: 768px) { #video-demo .video-container { max-width: min(50vw, 400px); margin-left: auto; margin-right: auto; } }

        /* 8. BENEFITS */
        #benefits { padding: clamp(2rem, 4vh, 3rem) 0; background-color: var(--bg-light); }
        .benefits-split { display: flex; flex-direction: column; gap: clamp(1.5rem, 3vh, 2rem); }
        @media(min-width: 768px) { .benefits-split { flex-direction: row; align-items: stretch; } }
        .benefit-col { flex: 1; padding: clamp(1.25rem, 2.5vh, 1.5rem); border-radius: var(--radius-md); }
        .problem-col { background-color: #fff1f2; border: 1px solid #fecdd3; }
        .solution-col { background-color: #eff6ff; border: 1px solid #bfdbfe; }
        .col-title { font-size: clamp(1.1rem, 2.5vw, 1.25rem); font-weight: 700; margin-bottom: clamp(0.75rem, 1.5vh, 1rem); display: flex; align-items: center; gap: 10px; }
        .problem-col .col-title { color: #e11d48; }
        .solution-col .col-title { color: #2563eb; }
        .benefit-list li { margin-bottom: clamp(0.6rem, 1.2vh, 0.75rem); position: relative; padding-left: clamp(1.25rem, 2.5vw, 1.5rem); font-size: clamp(0.9rem, 1.8vw, 1rem); }
        .problem-col li::before { content: "❌"; color: #e11d48; position: absolute; left: 0; }
        .solution-col li::before { content: "✅"; color: #2563eb; position: absolute; left: 0; font-weight: bold; }

        /* 9. OFFER SECTION */
        #offer { padding: clamp(2rem, 4vh, 3rem) 0; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); }
        .offer-card { background: white; max-width: min(90vw, 600px); margin: 0 auto; padding: clamp(1.5rem, 3vh, 2rem) clamp(1.25rem, 2.5vw, 1.5rem); border-radius: var(--radius-md); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 2px solid var(--primary-color); position: relative; }
        .discount-badge { position: absolute; top: 10px; right: 10px; background-color: #ef4444; color: white; width: clamp(50px, 8vw, 60px); height: clamp(50px, 8vw, 60px); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: clamp(0.8rem, 1.5vw, 0.9rem); z-index: 10; transform: rotate(15deg); }
        .price-container { margin: clamp(1rem, 2vh, 1.5rem) 0; text-align: center; }
        .old-price { text-decoration: line-through; color: var(--text-light); font-size: clamp(1.1rem, 2.5vw, 1.2rem); }
        .new-price { font-size: clamp(2.5rem, 6vw, 3.5rem); font-weight: 800; color: var(--primary-color); line-height: 1; }
        .delivery-note { font-size: clamp(0.75rem, 1.5vw, 0.8rem); color: #9ca3af; margin-top: 5px; text-align: center; }
        .offer-includes { text-align: left; max-width: 350px; margin: 0 auto clamp(1rem, 2vh, 1.5rem) auto; }
        .offer-includes li { padding: clamp(0.5rem, 1vh, 0.6rem) 0; border-bottom: 1px dashed var(--border-color); display: flex; align-items: center; font-size: clamp(0.9rem, 1.8vw, 1rem); }
        .offer-includes li::before { content: "✅"; color: var(--success-color); margin-right: 10px; font-weight: bold; }

        /* 10. REVIEWS */
        #reviews { padding: clamp(2rem, 4vh, 3rem) 0; background-color: var(--bg-white); overflow: hidden; }
        .reviews-header { display: flex; flex-direction: column; align-items: center; gap: clamp(0.75rem, 1.5vh, 1rem); margin-bottom: clamp(1.5rem, 3vh, 2rem); }
        @media(min-width: 768px) { .reviews-header { flex-direction: row; justify-content: space-between; } }
        .rating-summary { display: flex; align-items: center; gap: 10px; font-size: clamp(1rem, 2vw, 1.1rem); color: var(--text-main); }
        .rating-number { font-weight: 800; font-size: clamp(1.75rem, 4vw, 2rem); }
        .rating-stars-lg { color: var(--accent-color); font-size: clamp(1.1rem, 2.5vw, 1.25rem); }
        .reviews-container-box { position: relative; display: flex; align-items: center; justify-content: center; }
        .reviews-track { display: flex; gap: clamp(0.75rem, 2vw, 1rem); overflow-x: auto; scroll-snap-type: x mandatory; padding: clamp(0.75rem, 1.5vh, 1rem) 5px; scrollbar-width: none; width: 100%; }
        .reviews-track::-webkit-scrollbar { display: none; }
        .nav-btn { display: none; background: white; border: 1px solid var(--border-color); width: clamp(36px, 5vw, 40px); height: clamp(36px, 5vw, 40px); border-radius: 50%; align-items: center; justify-content: center; cursor: pointer; box-shadow: var(--shadow-md); z-index: 2; }
        @media(min-width: 768px) { .nav-btn { display: flex; } }
        .review-card { background: var(--bg-light); padding: clamp(1.25rem, 2.5vh, 1.5rem); border-radius: var(--radius-md); min-width: clamp(280px, 40vw, 300px); width: clamp(280px, 40vw, 300px); scroll-snap-align: center; flex-shrink: 0; border: 1px solid var(--border-color); display: flex; flex-direction: column; }
        .stars { color: var(--accent-color); margin-bottom: clamp(0.4rem, 0.8vh, 0.5rem); text-align: left; }
        .review-text { font-style: italic; margin-bottom: clamp(0.75rem, 1.5vh, 1rem); color: #4b5563; font-size: clamp(0.9rem, 1.8vw, 0.95rem); line-height: 1.5; overflow-wrap: break-word; text-align: left; }
        .reviewer-info { font-weight: 700; display: flex; align-items: center; gap: 10px; margin-top: auto; }
        .reviewer-avatar { width: clamp(28px, 4vw, 32px); height: clamp(28px, 4vw, 32px); background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: clamp(0.85rem, 1.5vw, 0.9rem); }

        /* Modal Styles */
        #review-modal, #success-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; padding: clamp(0.75rem, 2vw, 1rem); }
        .modal-content, .success-content { background: white; padding: clamp(1.25rem, 2.5vh, 1.5rem); border-radius: var(--radius-md); width: 100%; max-width: min(90vw, 450px); position: relative; }
        .close-modal { position: absolute; top: 10px; right: 15px; font-size: clamp(1.75rem, 4vw, 2rem); line-height: 1; cursor: pointer; background: none; border: none; color: #9ca3af; }
        .star-rating-input { display: flex; gap: 8px; justify-content: center; margin-bottom: clamp(0.75rem, 1.5vh, 1rem); cursor: pointer; }
        .star-rating-input span { font-size: clamp(2rem, 5vw, 2.5rem); color: #e5e7eb; transition: color 0.2s; }
        .star-rating-input span.active { color: var(--accent-color); }
        .success-content { text-align: center; padding: clamp(1.5rem, 3vh, 2rem); }
        .success-icon { font-size: clamp(3rem, 6vw, 4rem); margin-bottom: clamp(0.75rem, 1.5vh, 1rem); display: block; text-align: center; }

        /* 11. ORDER FORM */
        #order { padding: clamp(2rem, 4vh, 3rem) 0; background-color: var(--bg-light); scroll-margin-top: clamp(70px, 10vh, 90px); }
        .form-container { max-width: min(90vw, 550px); margin: 0 auto; background: white; padding: clamp(1rem, 2vh, 1.25rem); border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); }
        @media(min-width: 768px) { .form-container { padding: clamp(0.75rem, 2.5vh, 0.75rem); } }
        .form-group { margin-bottom: clamp(0.55rem, 1.1vh, 0.75rem); }
        .form-label { display: block; margin-bottom: clamp(0.25rem, 0.5vh, 0.3rem); font-weight: 700; color: var(--text-main); font-size: clamp(1rem, 1.9vw, 1rem); text-align: left; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: clamp(9px, 1.5vh, 10px) clamp(10px, 2vw, 12px); border: 1px solid #d1d5db; border-radius: var(--radius-sm); font-size: clamp(0.95rem, 2vw, 1rem); font-family: inherit; line-height: 1.5; }
        .form-input:focus { outline: none; border-color: var(--primary-color); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(8px, 1.5vw, 10px); }

        .bundles-container { display: flex; flex-direction: column; gap: clamp(8px, 1.5vh, 10px); }
        .bundle-card { border: 2px solid #e5e7eb; border-radius: var(--radius-sm); padding: clamp(10px, 1.8vh, 12px); display: flex; align-items: center; gap: clamp(10px, 2vw, 12px); cursor: pointer; transition: all 0.2s; position: relative; }
        .bundle-card.popular { border-color: var(--accent-color); background-color: #fffbeb; }
        .bundle-card:hover { background-color: #f9fafb; }
        .bundle-card:has(input:checked) { border-color: var(--primary-color); background-color: #fef2f2; }
        .bundle-radio { transform: scale(1.3); accent-color: var(--primary-color); }
        .bundle-info { flex: 1; display: flex; justify-content: space-between; align-items: center; }
        .bundle-title { font-weight: 700; font-size: clamp(0.9rem, 2vw, 1rem); }
        .bundle-price { font-weight: 800; font-size: clamp(1rem, 2.2vw, 1.1rem); color: var(--primary-color); }
        .bundle-save { font-size: clamp(0.75rem, 1.5vw, 0.8rem); color: #16a34a; font-weight: 600; background: #dcfce7; padding: 2px 6px; border-radius: 4px; }
        .popular-badge { position: absolute; top: -10px; right: 10px; background: var(--accent-color); color: white; font-size: clamp(0.65rem, 1.3vw, 0.7rem); font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
        .gift-badge { position: absolute; top: -10px; left: 10px; background: #10b981; color: white; font-size: clamp(0.65rem, 1.3vw, 0.7rem); font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }

        /* PLATĂ LA LIVRARE & TRANSPORT */
        .payment-options-container { display: flex; flex-direction: column; gap: clamp(8px, 1.5vh, 10px); }
        .payment-option { border: 2px solid var(--primary-color); border-radius: var(--radius-sm); padding: clamp(12px, 2vh, 14px); display: flex; align-items: center; gap: clamp(10px, 2vw, 12px); cursor: pointer; transition: all 0.2s; background-color: #fff1f7; font-weight: 600; }
        .payment-option:hover { background-color: #ffe4f0; }
        .payment-option input[type="radio"] { transform: scale(1.3); accent-color: var(--primary-color); }
        .payment-label { display: flex; align-items: center; gap: 8px; font-size: clamp(0.9rem, 2vw, 1rem); color: var(--text-main); }
        
        /* STILURI NOI PENTRU ADRESE */
        .form-input[readonly] { background-color: #f3f4f6; color: #6b7280; border-color: #e5e7eb; cursor: not-allowed; }
        .search-container { background: #e0f3e4; padding: 12px; border-radius: var(--radius-sm); border: 1px dashed #28a745; margin-bottom: 1rem; }
        
        /* CSS Extra pentru funcționalități */
        .disabled-option { opacity: 0.5; cursor: not-allowed; filter: grayscale(100%); background-color: #f3f4f6 !important; border-color: #d1d5db !important; }
        .order-summary { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin: 15px 0; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9em; color: #555; }
        .summary-total { border-top: 1px solid #ccc; margin-top: 8px; padding-top: 8px; display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em; color: #333; }
        .total-highlight { color: #eb2571; }
        
        /* STILURI NOI PREȚURI */
        .bundle-pricing { text-align: right; display: flex; flex-direction: column; align-items: flex-end; }
        .old-price { color: #999; text-decoration: line-through; font-size: 0.85em; margin-bottom: 2px; }
        .bundle-price { color: #eb2571; font-weight: bold; font-size: 1.1em; }
        .bundle-save { color: #16a34a; font-size: 0.8em; font-weight: 600; background: #dcfce7; padding: 2px 6px; border-radius: 4px; margin-top: 3px; display: inline-block; }

        .summary-price-wrapper { text-align: right; }
        .summary-old-price { color: #999; text-decoration: line-through; font-size: 0.8em; display: block; }
        .summary-new-price { color: #333; font-weight: 600; }
        
        .btn-loading { position: relative; color: transparent !important; pointer-events: none; }
        .btn-loading::after { content: ""; position: absolute; left: 50%; top: 50%; width: 20px; height: 20px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 0.8s linear infinite; transform: translate(-50%, -50%); }
        @keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }

        /* WIDGET ECOLET (Harta) - ASCUNDERE BARA CĂUTARE & STILIZARE */
        #bpWidget { 
            width: 100%; 
            height: 500px; 
            border: 2px solid var(--primary-color); 
            border-radius: 8px; 
            overflow: hidden; 
            position: relative;
        }
        /* Ascundem input-urile și bara de search din interiorul widgetului */
        #bpWidget .search-bar, 
        #bpWidget .bp-search-container, 
        #bpWidget input[type="search"], 
        #bpWidget input.bp-input, 
        #bpWidget .bp-input-wrapper { 
            display: none !important; 
        }
        /* Ajustăm headerul hărții */
        #bpWidget .bp-header { 
            padding: 0 !important; 
            min-height: 0 !important; 
            height: 0 !important; 
            overflow: hidden !important; 
        }

        /* Footer */
        #footer { background-color: #1f2937; color: #9ca3af; padding: clamp(2rem, 4vh, 2.5rem) clamp(1rem, 2vw, 1.5rem); text-align: center; }
        .footer-links { display: flex; justify-content: center; gap: clamp(0.75rem, 2vw, 1rem); margin-bottom: clamp(1rem, 2vh, 1.5rem); flex-wrap: wrap; font-size: clamp(0.85rem, 1.8vw, 0.9rem); }
        .footer-links a { color: #9ca3af; text-decoration: none; transition: color 0.3s ease; cursor: pointer; }
        .footer-links a:hover { color: var(--primary-color); text-decoration: underline; }
        .legal-text { font-size: clamp(0.75rem, 1.5vw, 0.8rem); max-width: 600px; margin: 0 auto; border-top: 1px solid #374151; padding-top: clamp(1.5rem, 3vh, 2rem); text-align: center; }
    </style>
</head>
<body>

    <header>
        <div class="container header-content">
            <div class="logo" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
                <span style="font-size: 1.6rem;">😻</span> Secretul Pisicii
            </div>
            <button class="btn btn-primary header-cta" onclick="scrollToSection('order')">
                Cumpără acum
            </button>
        </div>
    </header>

    <main>
        <section id="hero">
            <div class="container hero-grid">
                <div class="hero-media">
                    <div class="media-placeholder">
                        <video class="hero-video" autoplay loop muted playsinline>
                            <source src="assets/videohero1.mp4" type="video/mp4">
                            <img src="https://placehold.co/600x400?text=Video+Placeholder" alt="Demo Video">
                        </video>
                    </div>
                </div>

                <div class="hero-text">
                    <h1>Secretul Pisicii 😻<br>Adio Păr pe Haine!</h1>
                    <p class="hero-tagline">Tehnologia Nano-Steam care oferă pisicii tale un SPA, iar ție o casă sigură.</p>
                    
                    <ul class="hero-benefits">
                        <li>Colectează părul mort înainte să cadă pe haine</li>
                        <li>Nano-Steam: Igienizare cu abur cald și masaj relaxant</li>
                        <li>Rezervor special pentru uleiuri esențiale și soluții</li>
                    </ul>

                    <div class="hero-actions">
                        <button class="btn btn-primary btn-large" onclick="scrollToSection('order')">
                            Profită de Ofertă
                        </button>
                        <button class="btn btn-secondary btn-large" onclick="scrollToSection('video-demo')">
                            Vezi cum funcționează
                        </button>
                    </div>

                    <div class="trust-badges">
                        <span>🚚 Livrare Rapidă</span>
                        <span>🔒 Plată la Livrare</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="urgency">
            <div class="container urgency-box">
                <div class="stock-alert">
                    <span class="stock-dot"></span>
                    STOC LIMITAT: Doar câteva bucăți rămase!
                </div>
            </div>
        </section>

        <section id="video-demo">
            <div class="container">
                <div class="section-title">Vezi rezultatele 🎬</div>
                <div class="video-container">
                    <video class="hero-video" autoplay loop muted playsinline>
                        <source src="assets/videohero2.mp4" type="video/mp4">
                        <img src="https://placehold.co/800x450?text=Demo+Results">
                    </video>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <button class="btn btn-primary btn-large" onclick="scrollToSection('order')">
                        Vreau si eu peria!
                    </button>
                </div>
            </div>
        </section>

        <section id="benefits">
            <div class="container">
                <div class="section-title">Nu mai face compromisuri!</div>
                <div class="benefits-split">
                    <div class="benefit-col problem-col">
                        <div class="col-title">Fără Secretul Pisicii ❌</div>
                        <ul class="benefit-list">
                            <li>Haine, canapele și mâncare pline de păr</li>
                            <li>Aerul respirat este contaminat de microbi, alergeni, piele moarta și păr</li>
                            <li>Copiii inhalează praful biologic de pe covor</li>
                            <li>Pisica este chinuită de periatul clasic</li>
                        </ul>
                    </div>
                    <div class="benefit-col solution-col">
                        <div class="col-title">Cu Secretul Pisicii ✅</div>
                        <ul class="benefit-list">
                            <li>Colectare tip "clătită", fară păr în aer</li>
                            <li>Aer curat și igienă pentru întreaga familie</li>
                            <li>Haine negre care rămân negre, fără scame</li>
                            <li>Masaj relaxant care face pisica să toarcă</li>
                        </ul>
                    </div>
                </div>
                 <div style="display: flex; justify-content: center; margin-top: 2rem; width: 100%;">
                  <button class="btn btn-primary btn-large" onclick="scrollToSection('order')">
                     Vreau soluția acum
                  </button>
                 </div>
            </div>
        </section>

        <section id="offer">
            <div class="container">
                <div class="offer-card">
                    <div class="discount-badge">-30%</div>
                    <h2 class="section-title">Ofertă de Lansare </h2>
                    <div class="price-container">
                        <span class="old-price" style="text-align: center;">90 Lei</span>
                        <span class="new-price">59 Lei</span>
                        <p class="delivery-note">*Preț per bucată la pachetul standard<br>+14 lei livrare GLS</p>
                    </div>
                    <ul class="offer-includes">
                        <li>✨ Perie Nano-Steam Originală</li>
                        <li>✨ Cablu de încărcare inclus</li>
                        <li>✨ Manual de utilizare</li>
                    </ul>
                    <div style="display: flex; justify-content: center; margin-top: 2rem; width: 100%;">
                        <button class="btn btn-primary btn-large" onclick="scrollToSection('order')">
                          Vreau Oferta Acum 😻
                      </button>
                     </div> 
                </div>
            </div>
        </section>

        <section id="video-demo">
            <div class="container">
                <div class="video-container">
                    <video class="hero-video" autoplay loop muted playsinline>
                        <source src="assets/videohero2.mp4" type="video/mp4">
                        <img src="https://placehold.co/800x450?text=Demo+Results">
                    </video>
                </div>
            </div>
        </section>

        <section id="reviews">
            <div class="container">
                <div class="reviews-header">
                    <div>
                        <div class="section-title" style="margin-bottom: 5px; text-align: left;">Ce spun clienții</div>
                        <div id="rating-summary" class="rating-summary" style="display: none;">
                            <span class="rating-number" id="avg-rating-num">4.9</span>
                            <div class="rating-stars-lg">★★★★★</div>
                            <span class="rating-count-text">(<span id="total-reviews-summary">0</span>)</span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" id="add-review-btn" onclick="openReviewModal()">✍️ Adaugă recenzie</button>
                </div>

                <div class="reviews-container-box">
                    <button class="nav-btn" id="prevReview">&lt;</button>
                    <div class="reviews-track" id="reviews-track">
                        <div class="review-card">
                            <p class="review-text">Se încarcă...</p>
                        </div>
                    </div>
                    <button class="nav-btn" id="nextReview">&gt;</button>
                </div>
            </div>
        </section>

        <section id="order">
            <div class="container">
                <div class="section-title">Finalizează Comanda</div>
                <p class="section-subtitle">Completează datele de livrare mai jos</p>

                <div class="form-container">
                    <form id="order-form">
                        
                        <div class="form-group">
                            <label class="form-label">Alege Pachetul</label>
                            <div class="bundles-container">
                                
                                <label class="bundle-card">
                                    <input type="radio" name="bundle" value="1" class="bundle-radio" 
                                           data-price="59" data-old-price="90" data-savings="31"
                                           onchange="updateOrderTotal()">
                                    <div class="bundle-info">
                                        <span class="bundle-title">1 Bucată</span>
                                        <div class="bundle-pricing">
                                            <span class="old-price">90 Lei</span>
                                            <span class="bundle-price">59 Lei</span>
                                            <span class="bundle-save">Reducere 31 Lei</span>
                                        </div>
                                    </div>
                                </label>
                
                                <label class="bundle-card popular">
                                    <div class="popular-badge">Cel Mai Popular</div>
                                    <input type="radio" name="bundle" value="2" class="bundle-radio" 
                                           data-price="97" data-old-price="180" data-savings="83"
                                           checked onchange="updateOrderTotal()">
                                    <div class="bundle-info">
                                        <span class="bundle-title">2 Bucăți</span>
                                        <div class="bundle-pricing">
                                            <span class="old-price">180 Lei</span>
                                            <span class="bundle-price">97 Lei</span>
                                            <span class="bundle-save">Reducere 83 Lei</span>
                                        </div>
                                    </div>
                                </label>
                
                                <label class="bundle-card">
                                    <div class="gift-badge">🎁 +Jucărie Cadou</div>
                                    <input type="radio" name="bundle" value="3" class="bundle-radio" 
                                           data-price="133" data-old-price="270" data-savings="137"
                                           onchange="updateOrderTotal()">
                                    <div class="bundle-info">
                                        <span class="bundle-title">3 Bucăți</span>
                                        <div class="bundle-pricing">
                                            <span class="old-price">270 Lei</span>
                                            <span class="bundle-price">133 Lei</span>
                                            <span class="bundle-save">Reducere 137 Lei</span>
                                        </div>
                                    </div>
                                </label>

                            </div>
                        </div>
                
                        <div class="form-group">
                            <label class="form-label">Nume complet</label>
                            <input type="text" name="fullName" class="form-input" placeholder="Ex: Popescu Ion" required>
                        </div>
                
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="phone" class="form-input" placeholder="07xx..." required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input" placeholder="email@..." required>
                            </div>
                        </div>
                
                        <div class="form-group" style="margin-top: 1rem;">
                            
                            <div class="search-container">
                                <label class="form-label" style="font-size: 0.9em; color: #28a745;">🔍 Caută adresa (Scrie strada și numărul)</label>
                                <input type="text" id="addressSearch" class="form-input" placeholder="Începe să scrii adresa aici..." style="border: 2px solid #28a745;">
                            </div>

                            <label class="form-label">Detalii Livrare (Se completează automat)</label>
                            
                            <div class="form-group">
                                <input type="text" name="address_line" id="streetField" class="form-input" placeholder="Stradă + Număr" readonly required>
                            </div>
                            
                            <div class="form-row">
                                <input type="text" name="county" id="countyField" class="form-input" placeholder="Județ" readonly required>
                                <input type="text" name="city" id="cityField" class="form-input" placeholder="Localitate" readonly required>
                            </div>
                            
                            <div class="form-group" style="margin-top: 10px;">
                                <input type="text" name="postal_code" id="postalCodeField" class="form-input" placeholder="Cod Poștal" readonly>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1rem;">
                            <label class="form-label">Metoda de Transport</label>
                            <div class="payment-options-container">
                                <label class="payment-option">
                                    <input type="radio" name="shippingMethod" value="gls" checked onchange="toggleTransport('gls')">
                                    <span class="payment-label">🚚 GLS Curier (+14 Lei)</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="shippingMethod" value="easybox" onchange="toggleTransport('easybox')">
                                    <span class="payment-label">📦 EasyBox Locker (+10 Lei)</span>
                                </label>
                            </div>
                        </div>

                        <div id="easyboxContainer" style="display:none; margin-top: 1rem; text-align: center;">
                            <label class="form-label" style="color: var(--primary-color);">👇 Selectează locker-ul de pe hartă:</label>
                            
                            <div id="bpWidgetContainer">
                                </div>

                            <div id="lockerInfo" style="display:none; margin-top: 10px; padding: 12px; background-color: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; text-align: left;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="color: #1f2937;">EasyBox Selectat:</strong>
                                </div>
                                <strong id="lockerNameDisplay" style="color: #16a34a; font-size: 1.1em; display:block; margin-top:5px;"></strong>
                                <span id="lockerAddrDisplay" style="color: #4b5563; font-size: 0.9rem;"></span>
                            </div>
                            <input type="hidden" name="lockerId" id="selectedLockerId">
                                        <input type="hidden" name="lockerDetails" id="selectedLockerDetails">
                            <input type="hidden" name="lockerName" id="selectedLockerName">
                        </div>
                
                        <div class="order-summary">
                            <div class="summary-row">
                                <span id="summary-bundle-name">Pachet 2 Bucăți</span>
                                <div class="summary-price-wrapper">
                                    <span class="summary-old-price" id="summary-old-product-price">180 Lei</span>
                                    <span class="summary-new-price" id="summary-bundle-price">97 Lei</span>
                                </div>
                            </div>
                            <div class="summary-row">
                                <span id="summary-shipping-label">🚚 Transport GLS</span>
                                <span id="summary-shipping-cost">14 Lei</span>
                            </div>
                            <div class="summary-total">
                                <div>
                                    <span>TOTAL DE PLATĂ (TVA inclus)</span>
                                </div>
                                <div style="text-align: right;">
                                    <span class="total-highlight" id="summary-total">111 Lei</span>
                                    <span id="summary-savings" style="color: #28a745; font-size: 0.75em; display: block; font-weight: 600;">Reducere 83 Lei!</span>
                                </div>
                            </div>
                        </div>
                
                        <div class="form-group">
                            <label class="form-label">Metodă de plată</label>
                            <div class="payment-options-container">
                                <label class="payment-option" id="cashLabelBox">
                                    <input type="radio" name="paymentMethod" value="cash" checked onchange="updateOrderTotal()">
                                    <span class="payment-label">💵 Plată la livrare (Ramburs)</span>
                                </label>
                                <label class="payment-option" id="cardLabelBox">
                                    <input type="radio" name="paymentMethod" value="card" onchange="updateOrderTotal()">
                                    <span class="payment-label">💳 Plată cu Card (Netopia)</span>
                                </label>
                            </div>
                        </div>
                
                        <button type="submit" id="submit-btn" class="btn btn-primary btn-large" style="width: 100%; margin-top: clamp(8px, 1.5vh, 10px);">
                            Confirmă Comanda 📦
                        </button>
                
                        <div id="response-message"
                            style="display:none; margin-top:15px; padding:10px; text-align:center; border-radius:5px;"></div>
                    </form>
                
                    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $googleApiKey; ?>&libraries=places"></script>
                    
                    <script type="text/javascript" src="https://widget.bliskapaczka.pl/v7/main.js"></script>

                    <script>
                        // =====================================
                        // 1. LOGICA GOOGLE ADDRESS (AUTO-COMPLETE & CLEANING)
                        // =====================================
                        
                        window.cityForMap = "Romania"; 
                        window.streetForMap = "";

                        function initGooglePlaces() {
                            const searchInput = document.getElementById('addressSearch');
                            const streetField = document.getElementById('streetField');
                            const countyField = document.getElementById('countyField');
                            const cityField = document.getElementById('cityField');
                            const postalCodeField = document.getElementById('postalCodeField');

                            if (!searchInput || !window.google) return;

                            const autocomplete = new google.maps.places.Autocomplete(searchInput, { 
                                componentRestrictions: { country: 'ro' },
                                fields: ["address_components", "geometry", "name"],
                                types: ["address"]
                            });

                            autocomplete.addListener('place_changed', function() {
                                const place = autocomplete.getPlace();
                                streetField.value = "";
                                countyField.value = "";
                                cityField.value = "";
                                postalCodeField.value = "";
                                window.cityForMap = "";
                                window.streetForMap = "";

                                if (!place.address_components) return;

                                let street = "";
                                let number = "";

                                for (const component of place.address_components) {
                                    const type = component.types[0];
                                    switch (type) {
                                        case "route": 
                                            street = component.long_name; 
                                            window.streetForMap = street; // Salvăm strada simplă
                                            break;
                                        case "street_number": number = component.long_name; break;
                                        case "locality": 
                                            cityField.value = component.long_name; 
                                            window.cityForMap = component.long_name; // Salvăm orașul
                                            break;
                                        case "administrative_area_level_1": countyField.value = component.long_name; break;
                                        case "postal_code": postalCodeField.value = component.long_name; break;
                                    }
                                }

                                if(!cityField.value) {
                                     for (const component of place.address_components) {
                                        if (component.types[0] === "administrative_area_level_2") {
                                            cityField.value = component.long_name;
                                            window.cityForMap = component.long_name;
                                        }
                                     }
                                }

                                streetField.value = street + (number ? ", Nr. " + number : "");
                                
                                // RE-INIȚIALIZĂM HARTA DACĂ E SELECTATĂ
                                const currentMethod = document.querySelector('input[name="shippingMethod"]:checked').value;
                                if (currentMethod === 'easybox') {
                                    setTimeout(initEcoletMap, 200);
                                }

                                updateOrderTotal();
                            });
                        }

                        // =====================================
                        // 2. TRANSPORT & HARTA (FĂRĂ SEARCH BAR + RE-INIT ROBUST)
                        // =====================================
                        
                        function toggleTransport(method) {
                            const easyboxContainer = document.getElementById('easyboxContainer');
                            const cashRadio = document.querySelector('input[name="paymentMethod"][value="cash"]');
                            const cardRadio = document.querySelector('input[name="paymentMethod"][value="card"]');
                            const cashLabelBox = document.getElementById('cashLabelBox');

                            if (method === 'easybox') {
                                easyboxContainer.style.display = 'block';
                                
                                // Inițializăm harta de la zero
                                setTimeout(initEcoletMap, 50);

                                // Easybox obligă plata cu cardul
                                cashRadio.disabled = true;
                                cashRadio.checked = false;
                                cardRadio.checked = true;
                                cashLabelBox.classList.add('disabled-option');
                            } else {
                                easyboxContainer.style.display = 'none';
                                cashRadio.disabled = false;
                                cashLabelBox.classList.remove('disabled-option');
                            }
                            updateOrderTotal();
                        }

                        function initEcoletMap() {
                            // 1. GĂSIM CONTAINERUL MAMĂ
                            const wrapper = document.getElementById('bpWidgetContainer');
                            
                            // 2. ȘTERGEM ORICE HARTĂ VECHE (Soluția pentru "ecran alb")
                            wrapper.innerHTML = '<div id="bpWidget"></div>';

                            // 3. CONSTRUIM ADRESA SIMPLIFICATĂ (SOLUȚIA PENTRU "INTRAREA LEORDENI")
                            // Nu trimitem numărul străzii, doar Oraș + Stradă.
                            let startAddr = window.cityForMap || 'Romania';
                            if (window.streetForMap) {
                                startAddr = window.cityForMap + ", " + window.streetForMap;
                            }

                            console.log("Harta caută: " + startAddr);

                            // 4. CREĂM HARTA NOUĂ
                            BPWidget.init(
                                document.getElementById('bpWidget'), 
                                {
                                    callback: (point) => {
                                        console.log('Locker:', point);
                                        document.getElementById('selectedLockerId').value = point.code;
                                        document.getElementById('selectedLockerName').value = point.operator + ' - ' + point.address;
                                        // Format: "OPERATOR|NUME_LOCKER|ADRESA_FIZICA" — decodat de backend pentru Ecolet
                                        document.getElementById('selectedLockerDetails').value = point.operator + '|' + (point.name || point.description || point.address) + '|' + point.address;
                                        
                                        document.getElementById('lockerNameDisplay').innerText = point.operator + " (" + point.code + ")";
                                        document.getElementById('lockerAddrDisplay').innerText = point.address;
                                        document.getElementById('lockerInfo').style.display = 'block';
                                        document.getElementById('lockerInfo').scrollIntoView({behavior:'smooth'});
                                    },
                                    posType: 'DELIVERY',
                                    codOnly: false,
                                    showCod: true,
                                    language: 'ro',
                                    operatorMarkers: true,
                                    codeSearch: false, // ASCUNDE INPUTUL (API)
                                    initialAddress: startAddr, // CENTREAZĂ PE ADRESA SIMPLIFICATĂ
                                    countryCodes: 'RO',
                                    operators: [
                                        { operator: 'SAMEDAY' },
                                        { operator: 'FAN_COURIER' },
                                        { operator: 'CARGUS' },
                                        { operator: 'DPD' }
                                    ],
                                    alias: 'ecolet-192872'
                                }
                            );
                        }

                        // =====================================
                        // 3. CALCULE TOTAL & SUBMIT
                        // =====================================
                        function updateOrderTotal() {
                            const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked').value;
                            const shippingCost = (shippingMethod === 'easybox') ? 10 : 14;
                            const selectedRadio = document.querySelector('input[name="bundle"]:checked');
                            
                            if(selectedRadio) {
                                const price = parseInt(selectedRadio.getAttribute('data-price'));
                                const oldPrice = parseInt(selectedRadio.getAttribute('data-old-price'));
                                const savings = parseInt(selectedRadio.getAttribute('data-savings'));
                                
                                const total = price + shippingCost;
                                const totalSavings = savings;
                                
                                const bundleTitle = selectedRadio.closest('.bundle-card').querySelector('.bundle-title').innerText;
                                
                                document.getElementById('summary-bundle-name').innerText = "Pachet " + bundleTitle;
                                document.getElementById('summary-old-product-price').innerText = oldPrice + " Lei";
                                document.getElementById('summary-bundle-price').innerText = price + " Lei";
                                document.getElementById('summary-shipping-label').innerText = (shippingMethod === 'easybox' ? '📦 Transport Locker' : '🚚 Transport GLS');
                                document.getElementById('summary-shipping-cost').innerText = shippingCost + " Lei";
                                document.getElementById('summary-total').innerText = total + " Lei";
                                document.getElementById('summary-savings').innerText = "Reducere " + totalSavings + " Lei!";
                                
                                document.getElementById('submit-btn').innerText = "Confirmă Comanda 📦";
                            }
                        }
                
                        document.getElementById('order-form').addEventListener('submit', async function(e) {
                            e.preventDefault();
                            
                            const btn = document.getElementById('submit-btn');
                            const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked').value;
                            const lockerId = document.getElementById('selectedLockerId').value;
                            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;

                            if (shippingMethod === 'easybox' && !lockerId) {
                                alert("Te rugăm să alegi un locker de pe hartă!");
                                return;
                            }

                            const formData = new FormData(this);
                            const selectedRadio = document.querySelector('input[name="bundle"]:checked');
                            
                            const payload = {
                                fullName: formData.get('fullName'),
                                phone: formData.get('phone'),
                                email: formData.get('email'),
                                bundle: selectedRadio ? selectedRadio.value : '1',
                                price: selectedRadio ? selectedRadio.getAttribute('data-price') : '59',
                                paymentMethod: paymentMethod,
                                shippingMethod: shippingMethod,
                                lockerId: lockerId,
                                lockerDetails: formData.get('lockerDetails') || null,
                                address: {
                                    line: formData.get('address_line'), 
                                    county: formData.get('county'),
                                    city: formData.get('city'),
                                    postal_code: formData.get('postal_code')
                                }
                            };
                            
                            btn.disabled = true;
                            btn.classList.add('btn-loading');
                            btn.innerText = "Se procesează...";
                            
                            try {
                                const response = await fetch('order-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                                const result = await response.json();

                                if (result.success) {
                                    if (payload.paymentMethod === 'card') {
                                        const shippingCost = (shippingMethod === 'easybox') ? 10 : 14;
                                        const payRes = await fetch('netopia-payment.php?action=create', { method: 'POST', body: JSON.stringify({ orderId: result.data.orderId, amount: parseInt(payload.price) + shippingCost, email: payload.email }) });
                                        const payData = await payRes.json();
                                        if (payData.paymentUrl) window.location.href = payData.paymentUrl;
                                    } else {
                                        document.getElementById('success-modal').style.display = 'flex';
                                        this.reset();
                                        updateOrderTotal();
                                        if (typeof fbq !== 'undefined') fbq('track', 'Purchase', { value: parseInt(payload.price) + 14, currency: 'RON', content_name: 'Secretul Pisicii - Perie Nano-Steam' });
                                    }
                                } else {
                                    alert("Eroare: " + result.message);
                                }
                            } catch (err) {
                                alert("A apărut o eroare la trimitere. Te rugăm să încerci din nou.");
                            } finally {
                                btn.disabled = false;
                                btn.classList.remove('btn-loading');
                                btn.innerText = "Confirmă Comanda 📦";
                            }
                        });
                
                        document.addEventListener('DOMContentLoaded', () => {
                            updateOrderTotal();
                            try { initGooglePlaces(); } catch(e){}
                        });
                    </script>
                </div>
            </div>
        </section>
    </main>

<footer id="footer" style="text-align: center; background-color: #242d3a; padding: 2rem 0; color: white;">
    <div class="container" style="display: flex; flex-direction: column; align-items: center;">
        
        <h3 style="color: white; margin-bottom: 0.5rem; font-size: clamp(1.75rem, 4vw, 2rem); text-align: center; width: 100%;">
            Secretul Pisicii 😻
        </h3>

        <p style="margin-bottom: clamp(1.5rem, 3vh, 2rem); text-align: center; width: 100%;">
            Un brand <strong>Alvoro</strong> administrat de <strong>ALTMAR GROUP S.R.L.</strong>
        </p>

        <div class="footer-links" style="margin-bottom: 1.5rem; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; width: 100%;">
            <a href="termeni-conditii.html" style="color: white; text-decoration: none;">Termeni și condiții</a>
            <a href="politica-confidentialitate.html" style="color: white; text-decoration: none;">Politica de confidențialitate</a>
            <a href="politica-retur.html" style="color: white; text-decoration: none;">Politica de retur</a>
            <a href="politica-cookie.html" style="color: white; text-decoration: none;">Politica de cookie-uri</a>
        </div>

        <iframe src="https://mny.ro/npId.html?color=%23242d3a&version=orizontal&secret=161738" 
                style="border: none; width: 100%; max-width: 650px; height: 180px; display: block; margin: 0 auto;" 
                title="NETOPIA Payments">
        </iframe>

        <div class="anpc-badges"
            style="margin-top: 1.5rem; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; width: 100%;">
            <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener noreferrer">
                <img src="assets/SOL.svg" alt="SOL - Soluționarea Online a Litigiilor" style="height: 40px;">
            </a>

            <a href="https://www.anpc.ro/consumatori/sal/" target="_blank" rel="noopener noreferrer">
                <img src="assets/SAL.svg" alt="SAL - ANPC" style="height: 40px;">
            </a>
        </div>

        <p class="legal-text" style="margin-top: 1.5rem; text-align: center; width: 100%; opacity: 0.8;">
            © 2026 Secretul Pisicii. Toate drepturile rezervate.
        </p>
    </div>
</footer>

    <div id="cookie-banner">
        <p>Folosim cookie-uri pentru o experiență mai bună. 🍪</p>
        <div class="cookie-buttons">
            <button id="cookie-decline">Refuză</button>
            <button id="cookie-accept">Acceptă</button>
        </div>
    </div>

    <div id="success-modal">
        <div class="success-content">
            <span class="success-icon">✅</span>
            <h3 style="margin-bottom: clamp(0.75rem, 1.5vh, 1rem); font-size: clamp(1.6rem, 3.5vw, 1.8rem); color: #10b981;">Comandă Plasată!</h3>
            <p style="margin-bottom: clamp(0.75rem, 1.5vh, 1rem); font-size: clamp(1rem, 2.2vw, 1.1rem); color: #4b5563;">Îți mulțumim pentru comandă.</p>
            <p style="margin-bottom: clamp(1.5rem, 3vh, 2rem); color: #6b7280;">Te vom contacta telefonic în cel mai scurt timp pentru confirmare.</p>
            <button class="btn btn-primary" onclick="closeSuccessModal()" style="width: 100%;">Am înțeles</button>
        </div>
    </div>

    <div id="review-modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeReviewModal()">&times;</button>
            <h3 style="text-align: center; margin-bottom: clamp(0.75rem, 1.5vh, 1rem);">Lasă o recenzie</h3>
            <form id="new-review-form">
                <div class="star-rating-input" id="star-input">
                    <span data-val="1">★</span><span data-val="2">★</span><span data-val="3">★</span><span data-val="4">★</span><span data-val="5">★</span>
                </div>
                <input type="hidden" id="review-stars" value="5">
                <div class="form-group">
                    <input type="text" id="review-name" class="form-input" placeholder="Numele tău" required>
                </div>
                <div class="form-group">
                    <textarea id="review-text" class="form-input" placeholder="Opinia ta..." rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Postează</button>
            </form>
        </div>
    </div>

    <script>
        function scrollToSection(id) {
            const element = document.getElementById(id);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function openSuccessModal() { document.getElementById('success-modal').style.display = 'flex'; }
        function closeSuccessModal() { document.getElementById('success-modal').style.display = 'none'; }

        async function fetchReviews() {
            const track = document.getElementById('reviews-track');
            try {
                const response = await fetch('reviews_api.php');
                if (!response.ok) throw new Error('API Error');
                const reviews = await response.json();
                
                track.innerHTML = ''; 
                if (reviews.length === 0) {
                    addReviewDOM({name: "Maria T.", stars: 5, text: "Peria este minunată! Pisica mea o adoră."});
                    addReviewDOM({name: "Andrei P.", stars: 5, text: "Foarte eficientă, scapă de mult păr."});
                    return;
                }

                let totalStars = 0;
                reviews.forEach(r => { totalStars += parseInt(r.stars); addReviewDOM(r); });

                const avg = (totalStars / reviews.length).toFixed(1);
                document.getElementById('avg-rating-num').innerText = avg;
                document.getElementById('total-reviews-summary').innerText = reviews.length;
                document.getElementById('rating-summary').style.display = 'flex';

            } catch (err) {
                console.log('Using fallback reviews');
                track.innerHTML = '';
                addReviewDOM({name: "Maria T.", stars: 5, text: "Peria este minunată! Pisica mea o adoră."});
                addReviewDOM({name: "Andrei P.", stars: 5, text: "Foarte eficientă, scapă de mult păr."});
            }
        }

        function addReviewDOM(review) {
            const track = document.getElementById('reviews-track');
            const div = document.createElement('div');
            div.className = 'review-card';
            const stars = '★'.repeat(parseInt(review.stars) || 5);
            const letter = review.name ? review.name.charAt(0).toUpperCase() : 'C';
            
            div.innerHTML = `
                <div class="stars">${stars}</div>
                <p class="review-text">"${review.text}"</p>
                <div class="reviewer-info">
                    <div class="reviewer-avatar">${letter}</div>
                    <span>${review.name}</span>
                </div>
            `;
            track.appendChild(div);
        }

        function initReviews() {
            if(localStorage.getItem('hasReviewed')) {
                const btn = document.getElementById('add-review-btn');
                if(btn) {
                    btn.innerText = "✅ Ai adăugat o recenzie";
                    btn.disabled = true;
                    btn.style.opacity = "0.7";
                    btn.onclick = null;
                }
            }

            fetchReviews(); 
            
            const form = document.getElementById('new-review-form');
            const starInputs = document.querySelectorAll('#star-input span');
            const starHidden = document.getElementById('review-stars');

            starInputs.forEach(s => s.addEventListener('click', function() {
                const val = this.dataset.val;
                starHidden.value = val;
                starInputs.forEach(star => star.classList.toggle('active', star.dataset.val <= val));
            }));
            starInputs.forEach(star => star.classList.add('active'));

            if(form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    if(localStorage.getItem('hasReviewed')) {
                        alert("Ai adăugat deja o recenzie.");
                        closeReviewModal();
                        return;
                    }

                    const btn = form.querySelector('button');
                    const originalText = btn.innerText;
                    btn.innerText = "Se trimite...";
                    btn.disabled = true;

                    const data = {
                        name: document.getElementById('review-name').value,
                        stars: starHidden.value,
                        text: document.getElementById('review-text').value
                    };
                    
                    try {
                        const res = await fetch('reviews_api.php', {
                            method:'POST', 
                            headers:{'Content-Type':'application/json'}, 
                            body:JSON.stringify(data)
                        });
                        
                        const result = await res.json();

                        if(res.ok) {
                            form.reset(); 
                            closeReviewModal(); 
                            fetchReviews();
                            
                            localStorage.setItem('hasReviewed', 'true');
                            
                            const addBtn = document.getElementById('add-review-btn');
                            addBtn.innerText = "✅ Ai adăugat o recenzie";
                            addBtn.disabled = true;
                            addBtn.style.opacity = "0.7";
                            addBtn.onclick = null;

                            alert("Mulțumim pentru recenzie!");
                        } else { 
                            alert(result.error || 'Eroare la salvare.'); 
                        }
                    } catch(err) { 
                        alert('Eroare conexiune.'); 
                    }
                    
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
            }

            const track = document.getElementById('reviews-track');
            document.getElementById('prevReview')?.addEventListener('click', () => track.scrollBy({left: -320, behavior:'smooth'}));
            document.getElementById('nextReview')?.addEventListener('click', () => track.scrollBy({left: 320, behavior:'smooth'}));
        }

        function openReviewModal() { 
            if(localStorage.getItem('hasReviewed')) {
                alert("Ai adăugat deja o recenzie.");
                return;
            }
            document.getElementById('review-modal').style.display = 'flex'; 
        }
        function closeReviewModal() { document.getElementById('review-modal').style.display = 'none'; }
        window.onclick = e => { if(e.target == document.getElementById('review-modal')) closeReviewModal(); }

        function initCookie() {
            const banner = document.getElementById('cookie-banner');
            if(!localStorage.getItem('cookieConsent')) banner.style.display = 'flex';
            document.getElementById('cookie-accept').onclick = () => { localStorage.setItem('cookieConsent','accepted'); banner.style.display='none'; };
            document.getElementById('cookie-decline').onclick = () => { localStorage.setItem('cookieConsent','declined'); banner.style.display='none'; };
        }

        document.addEventListener('DOMContentLoaded', () => {
            initReviews();
            initCookie();
        });
    </script>
    <div id="processing-overlay" style="display:none;">
        <div class="processing-card">
            <div class="spinner"></div>
            <div class="processing-title">Se procesează comanda...</div>
            <div class="processing-steps" id="processing-steps">
                Verificăm datele · Rezervăm produsul · Confirmăm comanda
            </div>
        </div>
    </div>
    
</body>
</html>