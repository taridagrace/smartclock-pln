<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login HRD - SmartClock</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#edf2f7] font-[Poppins] flex justify-center items-center min-h-screen">

  <div class="bg-white shadow-lg rounded-2xl p-8 w-[350px]">
    <div class="text-center mb-6">
      <img src="assets/logo_pln.png" class="mx-auto w-16 mb-2" />
      <h2 class="text-xl font-semibold text-[#69c7d9]">SmartClock HRD</h2>
    </div>

    <form id="loginForm">
      <input type="email" name="email" placeholder="Email" required class="w-full p-3 mb-3 border rounded-md" />
      <input type="password" name="password" placeholder="Password" required class="w-full p-3 mb-4 border rounded-md" />
      <button type="submit" class="w-full bg-[#69c7d9] text-white py-2 rounded-md font-semibold hover:bg-[#4fb6cb]">Login</button>
    </form>

    <p id="errorMsg" class="text-red-500 text-center mt-3 text-sm"></p>
  </div>

  <script>
    document.getElementById("loginForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const res = await fetch("api/login.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.status === "ok") {
        window.location.href = "dashboard.html";
      } else {
        document.getElementById("errorMsg").textContent = data.message;
      }
    });
  </script>
</body>
</html>
