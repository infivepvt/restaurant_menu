<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>404 - Page Not Found</title>
<link rel="icon" type="image/x-icon" href="assets/image/Logo1.ico">
<style>
  body {
    margin: 0;
    padding: 0;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background: linear-gradient(135deg, #74ebd5 0%, #ACB6E5 100%);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
  }
  .container {
    text-align: center;
    background: rgba(0, 0, 0, 0.3);
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  }
  h1 {
    font-size: 72px;
    margin: 0 0 20px;
    letter-spacing: 2px;
    animation: pulse 2s infinite;
  }
  p {
    font-size: 20px;
    margin: 0 0 30px;
  }
  a.button {
    display: inline-block;
    background-color: #fff;
    color: #333;
    padding: 15px 30px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
    transition: background-color 0.3s, transform 0.2s;
  }
  a.button:hover {
    background-color: #f39c12;
    color: #fff;
    transform: scale(1.05);
  }

  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }
</style>
</head>
<body>
  <div class="container">
    <h1>404</h1>
    <p>Oops! Looks like the page you’re searching for doesn’t exist.</p>
    <a class="button" href="/">Go Home</a>
  </div>
</body>
</html>