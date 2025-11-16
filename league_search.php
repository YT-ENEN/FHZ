<?php
// search_images.php - 伺服器本地檔案搜尋腳本

// ----------------------------------------------------
// 伺服器端 PHP 檔案檢查邏輯 (接收 AJAX 請求)
// ----------------------------------------------------

// 檢查是否為 AJAX POST 請求，如果是，則執行檔案檢查並返回 JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_date'])) {
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '未知錯誤', 'images' => []];
    
    // 1. 取得並驗證使用者傳來的日期 (格式為 YYYY-MM-DD)
    $selected_date_raw = $_POST['search_date'];
    $date_object = DateTime::createFromFormat('Y-m-d', $selected_date_raw);
    
    if (!$date_object) {
        $response['message'] = '無效的日期格式。';
    } else {
        // 轉換為目標檔名格式：YYYYMMDD
        $date_prefix = $date_object->format('Ymd');
        
        // 2. 定義檔案路徑 (相對於網站根目錄)
        $upload_dir = 'uploads/'; 
        $img_files = [];
        $found_count = 0;
        
        // 3. 檢查兩個目標檔案是否存在
        $target_files = [
            'cover' => ["filename" => "{$date_prefix}-1.jpg", "label" => "直播封面"], // 直播封面
            'score' => ["filename" => "{$date_prefix}-2.jpg", "label" => "積分畫面"], // 積分畫面
        ];

        foreach ($target_files as $key => $file_info) {
            $filename = $file_info['filename'];
            $full_path = $upload_dir . $filename;
            
            // 使用 file_exists 檢查伺服器本地檔案是否存在
            if (file_exists($full_path)) {
                // 檔案存在，將其相對 URL 路徑和原始檔名加入回傳陣列
                $img_files[$key] = [
                    'url' => $full_path,
                    'filename' => $filename,
                    'label' => $file_info['label']
                ];
                $found_count++;
            } else {
                // 檔案不存在
                $img_files[$key] = [
                    'url' => '',
                    'filename' => $filename,
                    'label' => $file_info['label']
                ];
            }
        }
        
        $response['success'] = true;
        $response['images'] = $img_files;
        $response['search_date_display'] = $selected_date_raw;
        
        if ($found_count === 2) {
            $response['message'] = '✅ 兩張圖片皆已找到並顯示，點擊即可下載。';
        } elseif ($found_count === 1) {
            $response['message'] = '⚠️ 找到一張圖片，另一張不存在。';
        } else {
            $response['message'] = '當日無對應的圖片記錄。';
        }
    }
    
    // 返回 JSON 響應並結束 PHP 腳本
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./FHZ.ico" type="image/x-icon">
    <title>上帝賽事資料查詢</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* ---------------------------------- */
    /* 排版美化與樣式調整 */
    /* ---------------------------------- */
    body {
      background-color: transparent;
    }
    .container {
      background-color: rgb(124, 124, 124 ,0.9); 
      padding: 30px; 
      border-radius: 12px; 
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5); 
    }
    .search-container {
      display: flex;
      align-items: center;
      gap: 15px; 
      background-color: rgba(0, 0, 0, 0.2);
      padding: 10px;
      border-radius: 8px;
    }
    .search-input {
      width: 80%;
      padding: 0.5rem 0.75rem; 
      border-radius: 0.5rem;
      border: 1px solid #ced4da;
    }
    .search-button {
      flex: 1;
    }
    .page-title {
      text-align: center;
      font-family: Arial, sans-serif;
      font-size: 30px;
      font-weight: 1000;
      margin-bottom: 40px;
      padding: 30px;
      color: rgb(255, 255, 255);
    }
    .player-info {
        background-color: rgba(0, 0, 0, 0.3); /* 資訊區塊背景 */
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px !important;
    }
    .player-info h5 {
      color: yellow;
      font-size: 1.5rem; /* 增大日期標題 */
      border-bottom: 2px solid #ffc107; /* 黃色底線 */
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    .image-block {
        margin-bottom: 30px;
    }
    .image-block p {
        color: #fff;
        font-weight: bold;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .image-link {
        display: block;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        border-radius: 5px;
    }
    .image-link img {
      width: 100%;
      max-width: 640px;
      height: auto;
      margin: 0; 
      border-radius: 5px;
      display: block; 
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
      transition: transform 0.3s ease;
    }
    .image-link:hover img {
        transform: scale(1.02); /* 滑鼠懸停縮放效果 */
    }
    .download-btn {
        width: auto;
        padding: 5px 15px;
        font-size: 0.9rem;
        background-color: #28a745; /* 綠色下載按鈕 */
        border-color: #28a745;
    }
    .download-btn:hover {
        background-color: #1e7e34;
        border-color: #1c7430;
    }
    .info-msg {
        color: #ffc107; 
        background-color: rgba(0, 0, 0, 0.4);
        padding: 10px;
        border-radius: 5px;
        margin-top: 15px;
        text-align: center;
        font-weight: bold;
    }
    /* 新增行動裝置提醒樣式 */
    .mobile-tip {
        color: #ffcc00; /* 亮黃色 */
        background-color: rgba(0, 0, 0, 0.5);
        padding: 10px;
        border-radius: 5px;
        font-size: 0.9rem;
        margin-top: 15px;
        text-align: center;
        border: 1px solid #ffcc00;
    }
    </style>
</head>
<body>
    <h3 class="page-title">上帝賽事資料查詢</h3>
    <div class="container">
        <div class="row">
            <div class="col">
                <form id="search-form" class="form-inline" onsubmit="searchImages(); return false;">
                    <div class="search-container">
                        <img src="./FHZ_files/FHZ1.png" alt="Search Icon" width="48" height="48">
                        <input class="search-input" type="date" id="searchtext" name="searchtext" required> 
                        <button class="search-button btn btn-primary" type="submit">搜尋</button>
                    </div>
                </form>
                <div id="search-results" class="mt-3"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
// 找不到檔案時顯示的預設圖片
const NO_IMAGE_URL = 'https://via.placeholder.com/640x360?text=Image+Not+Found';

function searchImages() {
    let searchText = document.getElementById('searchtext').value.trim();
    const searchResultsContainer = document.getElementById('search-results');
    searchResultsContainer.innerHTML = ''; 
    
    if (!searchText) {
        alert('請選擇要搜尋的日期');
        return;
    }
    
    const formData = new FormData();
    formData.append('search_date', searchText); 

    axios.post('<?php echo basename(__FILE__); ?>', formData)
        .then(response => {
            const data = response.data;
            
            if (data.success) {
                displaySearchResults(data.images, data.search_date_display, data.message);
            } else {
                searchResultsContainer.innerHTML = `<p class="info-msg">${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('檔案搜尋失敗:', error);
            searchResultsContainer.innerHTML = '<p class="info-msg">網路或伺服器連線錯誤，請檢查 PHP 檔案。</p>';
        });
}

/**
 * 顯示從 PHP 腳本取得的圖片路徑
 */
function displaySearchResults(images, searchDate, message) {
    const searchResultsContainer = document.getElementById('search-results');
    searchResultsContainer.innerHTML = '';
    
    // 顯示結果訊息
    const infoMsg = document.createElement('p');
    infoMsg.classList.add('info-msg');
    infoMsg.textContent = message;
    searchResultsContainer.appendChild(infoMsg);


    const playerInfo = document.createElement('div');
    playerInfo.classList.add('player-info', 'mt-4'); 
    
    const dateDisplay = document.createElement('h5');
    dateDisplay.textContent = `— ${searchDate} 圖片記錄 —`;
    playerInfo.appendChild(dateDisplay);

    // 處理兩張圖片
    const imageKeys = ['cover', 'score'];
    imageKeys.forEach(key => {
        const imageData = images[key];
        const imgBlock = document.createElement('div');
        imgBlock.classList.add('image-block');

        const imgPath = imageData.url || NO_IMAGE_URL;
        const isFound = !!imageData.url;

        // 圖片標題
        const imgTitle = document.createElement('p');
        imgTitle.innerHTML = `<span>${imageData.label}</span>`;
        
        // 下載按鈕 (只有找到檔案才顯示)
        if (isFound) {
             const downloadLink = document.createElement('a');
             downloadLink.href = imgPath;
             downloadLink.download = imageData.filename; // 檔案名稱作為下載檔名
             downloadLink.classList.add('btn', 'download-btn', 'ms-auto');
             downloadLink.textContent = '下載圖片';
             
             // *** 優化行動裝置下載行為：點擊按鈕後，直接觸發下載，而不是新開連結 ***
             downloadLink.addEventListener('click', function(e) {
                // 確保 download 屬性生效
                // 註：在 iOS 上，用戶仍需手動儲存開啟的圖片
             });

             imgTitle.appendChild(downloadLink);
        }

        // 圖片連結 (點擊圖片即可下載，在行動裝置上通常是新開頁面)
        const imgLink = document.createElement('a');
        imgLink.classList.add('image-link');
        
        if (isFound) {
            imgLink.href = imgPath;
            // 讓點擊圖片也嘗試下載，但行動裝置通常會在新視窗開啟
            imgLink.download = imageData.filename; 
            imgLink.target = '_blank'; // 鼓勵在新分頁開啟，方便用戶長按儲存
        } else {
            imgLink.href = '#';
        }
        
        const imgElement = document.createElement('img');
        imgElement.src = imgPath;
        imgElement.alt = imageData.label;

        imgLink.appendChild(imgElement);
        
        imgBlock.appendChild(imgTitle);
        imgBlock.appendChild(imgLink);
        playerInfo.appendChild(imgBlock);
    });

    searchResultsContainer.appendChild(playerInfo);
    // 在結果底部添加行動裝置提醒
    const mobileTip = document.createElement('p');
    mobileTip.classList.add('mobile-tip');
    mobileTip.innerHTML = '💡 行動裝置存圖提示： 點擊「下載圖片」後，長按圖片下載或長按新開啟的圖片，手動選擇「儲存圖片」才能存到相簿喔！';
    searchResultsContainer.appendChild(mobileTip);
}
</script>
</body>
</html>