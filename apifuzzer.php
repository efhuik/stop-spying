<?php

function callAPI($method, $url, $data = false, $files = false) {
    $curl = curl_init();

    // Handle method
    switch (strtoupper($method)) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            // If sending a file, use multipart/form-data
            if ($files) {
                $data = ["file" => curl_file_create($files["tmp_name"], $files["type"], $files["name"])];
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        break;

        case "GET":
            if ($data) {
                $url .= "?" . http_build_query($data);
            }
        break;

        default:
            return "Unsupported method.";
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    $error    = curl_error($curl);

    curl_close($curl);

    return $error ? "cURL Error: $error" : $response;
}

$response = "";

// Handle **Text-based requests**
if (isset($_POST["mode"]) && $_POST["mode"] === "text") {
    $method   = $_POST["method"];
    $endpoint = $_POST["endpoint"];
    $postData = $_POST["postdata"];

    $dataArray = json_decode($postData, true);
    if ($postData !== "" && $dataArray === null) {
        $dataArray = $postData;
    }

    $response = callAPI($method, $endpoint, $method === "POST" ? $dataArray : false);
}

// Handle **File upload request**
if (isset($_POST["mode"]) && $_POST["mode"] === "file") {
    $endpoint = $_POST["file_endpoint"];
    $file     = $_FILES["upload_file"];

    if ($file && $file["error"] === 0) {
        $response = callAPI("POST", $endpoint, false, $file);
    } else {
        $response = "No file selected or error uploading.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>API Dashboard</title>
    <style>
        body {
            background: #0f0f0f;
            color: white;
            font-family: Arial;
            margin: 0;
            padding: 0;
        }
        .tabs {
            display: flex;
            background: #1a1a1a;
        }
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            background: #1a1a1a;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            background: #222;
            border-bottom: 3px solid #007bff;
        }
        .content {
            padding: 20px;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background: #222;
            border: 1px solid #333;
            color: #fff;
            border-radius: 6px;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background: #007bff;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
        }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>

    <script>
        function switchTab(tab) {
            document.getElementById("text_tab").classList.remove("active");
            document.getElementById("file_tab").classList.remove("active");

            document.getElementById("text_content").style.display = "none";
            document.getElementById("file_content").style.display = "none";

            document.getElementById(tab + "_tab").classList.add("active");
            document.getElementById(tab + "_content").style.display = "block";
        }
    </script>
</head>
<body>

<div class="tabs">
    <div class="tab active" id="text_tab" onclick="switchTab('text')">Send Request</div>
    <div class="tab" id="file_tab" onclick="switchTab('file')">Upload File</div>
</div>

<div class="content">

    <!-- TEXT REQUEST TAB -->
    <div id="text_content">
        <form method="POST">
            <input type="hidden" name="mode" value="text">

            <label>API Endpoint</label>
            <input type="text" name="endpoint" placeholder="https://discord.com/api/v9/..." required>

            <label>Method</label>
            <select name="method">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
            </select>

            <label>POST Body</label>
            <textarea name="postdata" rows="5" placeholder='{"key": "value"}'></textarea>

            <button type="submit">Send</button>
        </form>
    </div>

    <!-- FILE UPLOAD TAB -->
    <div id="file_content" style="display:none;">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="file">

            <label>API Endpoint</label>
            <input type="text" name="file_endpoint" placeholder="https://example.com/upload" required>

            <label>Select File</label>
            <input type="file" name="upload_file" required>

            <button type="submit">Upload to API</button>
        </form>
    </div>

    <?php if ($response !== ""): ?>
        <h3>Response:</h3>
        <pre><?php echo htmlspecialchars($response); ?></pre>
    <?php endif; ?>

</div>
</body>
</html>
