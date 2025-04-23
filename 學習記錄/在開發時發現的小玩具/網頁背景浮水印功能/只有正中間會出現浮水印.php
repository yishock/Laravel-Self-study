<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>有浮水印的頁面</title>
    <style>
        body {
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none; /* 不影響使用者互動 */
            z-index: 9999;
            opacity: 0.1;
            background-repeat: repeat;
            background-image:
                repeating-linear-gradient(
                    45deg,
                    transparent 0px,
                    transparent 80px,
                    rgba(0, 0, 0, 0.15) 80px,
                    rgba(0, 0, 0, 0.15) 160px
                );
        }

        .watermark::before {
            content: "<?php echo 'ihdfiodshjkluwio'; ?>";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 48px;
            color: #000;
            opacity: 0.08;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="watermark"></div>

<div style="padding: 50px;">
    <h1>這是一份有浮水印的文件</h1>
    <p>內容依然可以正常閱讀。</p>
</div>

</body>
</html>
