<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>SVG 浮水印示例</title>
  <style>
    body {
      background-image: url('data:image/svg+xml;base64,<?php echo base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
  <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle"
        fill="rgba(0,0,0,0.05)" font-size="20" transform="rotate(-30 100 100)">
    UserABC - 機密
  </text>
</svg>') ?>');
      background-repeat: repeat;
      background-size: 200px 200px;
      padding: 50px;
      font-family: sans-serif;
    }
    .content {
      background: white;
      padding: 2em;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="content">
  <h1>這是有浮水印的頁面</h1>
  <p>這段內容是測試用，浮水印是「<?php echo 1234354365765; ?>」會重複出現在整頁背景，並不影響文字閱讀。</p>
</div>

</body>
</html>
