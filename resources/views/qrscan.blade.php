<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR Scanner (ZXing)</title>
  <script src="https://unpkg.com/@zxing/library@latest"></script>
  <style>
    body { font-family: Arial, sans-serif; text-align: center; }
    video {
      width: 90%;
      max-width: 400px;
      border: 2px solid #333;
      border-radius: 8px;
      margin-top: 10px;
    }
    #userInfo {
      margin-top: 20px;
      text-align: left;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      background: #f9f9f9;
    }
    #userInfo img {
      width: 100px;
      border-radius: 50%;
    }
    #restartBtn {
      margin-top: 15px;
      padding: 8px 15px;
      border: none;
      background: #007bff;
      color: white;
      border-radius: 5px;
      cursor: pointer;
    }
    #restartBtn:hover { background: #0056b3; }
  </style>
</head>
<body>
  <h2>📷 QR Code Scanner (ZXing)</h2>
  <button id="startBtn">Start Scanner</button>
  <br>
  <video id="video"></video>
  <div id="result">Press "Start Scanner"</div>
  <div id="userInfo" style="display:none;"></div>
  <button id="restartBtn" style="display:none;">Scan Again</button>

  <script>
    const video = document.getElementById("video");
    const resultDiv = document.getElementById("result");
    const userInfo = document.getElementById("userInfo");
    const startBtn = document.getElementById("startBtn");
    const restartBtn = document.getElementById("restartBtn");

    let codeReader;

    async function startScanner() {
      codeReader = new ZXing.BrowserQRCodeReader();
      resultDiv.innerText = "Scanning...";
      video.style.display = "block";
      userInfo.style.display = "none";
      restartBtn.style.display = "none";

      try {
        // Corrected: listVideoInputDevices called on the instance
        const devices = await codeReader.listVideoInputDevices();
        if (!devices || devices.length === 0) {
          resultDiv.innerText = "⚠️ No camera devices found";
          return;
        }

        const selectedDeviceId = devices.length > 1 ? devices[1].deviceId : devices[0].deviceId;

        await codeReader.decodeFromVideoDevice(selectedDeviceId, "video", (result, err) => {
          if (result) {
            try {
              const data = JSON.parse(result.text);
              showUserData(data);
            } catch (e) {
              resultDiv.innerText = "✅ Scanned: " + result.text;
            }
            stopScanner();
          }
        });
      } catch (err) {
        resultDiv.innerText = "⚠️ Error: " + err.message;
      }
    }

    function stopScanner() {
      if (codeReader) {
        codeReader.reset();
      }
      video.style.display = "none";
      restartBtn.style.display = "inline-block";
    }

    function showUserData(data) {
      resultDiv.style.display = "none";
      userInfo.style.display = "block";

      userInfo.innerHTML = `
        <h3>User Info</h3>
        <p><strong>ID:</strong> ${data.id}</p>
        <p><strong>Name:</strong> ${data.name}</p>
        <p><strong>Email:</strong> ${data.email}</p>
        <p><strong>Status:</strong> ${data.status}</p>
        <hr>
        <h4>Details</h4>
        <p><strong>Username:</strong> ${data.details.user_name}</p>
        <p><strong>Phone:</strong> ${data.details.phone}</p>
        <p><strong>DOB:</strong> ${data.details.dob}</p>
        <p><strong>Gender:</strong> ${data.details.gender}</p>
        <p><strong>Pronouns:</strong> ${data.details.pronouns}</p>
        <p><strong>Bio:</strong> ${data.details.bio}</p>
        <img src="${data.details.profile_image}" alt="Profile Image">
      `;
    }

    startBtn.addEventListener("click", startScanner);
    restartBtn.addEventListener("click", startScanner);
  </script>
</body>
</html>
