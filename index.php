<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF--8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://www.blacnova.net/img/bn_orange.png" type="image/png">
    <title>Blacnova Dashboard</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            overflow: hidden;
        }
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            transition: opacity 0.5s ease-out;
        }
        .shimmer-wrapper {
            width: 100px;
            height: 100px;
            position: relative;
        }
        .shimmer {
            width: 100%;
            height: 100%;
            background: linear-gradient(100deg, rgba(255, 255, 255, 0) 20%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 80%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite linear;
        }
        .logo-shimmer {
            width: 80px;
            height: 80px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-image: url('https://www.blacnova.net/img/bn.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        #content-wrapper {
            visibility: hidden;
            height: 100vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>

    <div id="loader">
        <div class="shimmer-wrapper">
            <div class="logo-shimmer"></div>
            <div class="shimmer"></div>
        </div>
    </div>

    <div id="content-wrapper">
        <?php
            // Include the content of index.html
            readfile('index.html');
        ?>
    </div>

    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('loader');
            const content = document.getElementById('content-wrapper');

            // Hide loader
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
                
                // Show content
                content.style.visibility = 'visible';
                document.body.style.overflow = 'auto'; // Restore scrolling

            }, 500);
        });
    </script>

</body>
</html>
