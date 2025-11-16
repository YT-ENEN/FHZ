<?php
// 定義您的上傳密碼
const UPLOAD_PASSWORD = '24865678'; // <<<<< 請務必修改為您自己的密碼！

// 定義目標資料夾名稱，它會與此 PHP 檔案在同一層
$upload_dir = 'uploads/';
$message = ''; // 用來儲存給使用者的訊息

// --- 處理檔案上傳的邏輯 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 密碼驗證步驟 ---
    $submitted_password = $_POST['password'] ?? '';
    
    if ($submitted_password !== UPLOAD_PASSWORD) {
        $message = "<div style='color: red; font-weight: bold;'>❌ 錯誤：密碼不正確！請重新輸入。</div>";
        // 密碼錯誤，直接跳過後續的檔案處理
    } else {
        // --- 密碼正確，開始處理檔案 ---
    
        // 1. 取得並驗證使用者選擇的日期
        $selected_date_raw = $_POST['event_date'] ?? '';
        
        // 嘗試將使用者輸入的日期轉換為 YYYYMMDD 格式
        $date_object = DateTime::createFromFormat('Y-m-d', $selected_date_raw);
        
        if (!$date_object) {
            $message = "<div style='color: red; font-weight: bold;'>❌ 錯誤：請選擇一個有效的日期！</div>";
        } else {
            // 轉換為目標檔名格式：YYYYMMDD
            $date_prefix = $date_object->format('Ymd');
            
            // 2. 定義兩個要處理的檔案欄位
            $files_to_process = [
                'cover_image' => "{$date_prefix}-1.jpg", // 直播封面：-1
                'score_image' => "{$date_prefix}-2.jpg", // 積分畫面：-2
            ];
            
            $success_count = 0;
            $all_files_ok = true;

            // 3. 遍歷處理兩個檔案上傳
            foreach ($files_to_process as $input_name => $new_filename) {
                
                // 檢查該檔案欄位是否有上傳
                if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                    
                    $temp_path = $_FILES[$input_name]['tmp_name'];
                    $target_path = $upload_dir . $new_filename;
                    
                    // 檢查目標資料夾是否存在，不存在則嘗試建立 (新增資料夾檢查)
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // 嘗試將暫存檔案移動到指定路徑
                    if (move_uploaded_file($temp_path, $target_path)) {
                        $success_count++;
                        $message .= "<div style='color: green;'>✅ 檔案 **{$new_filename}** 上傳成功！</div>";
                    } else {
                        // 寫入失敗 (權限問題)
                        $all_files_ok = false;
                        $message .= "<div style='color: red;'>❌ **上傳失敗**：檔案 **{$new_filename}** 無法寫入。請檢查 `{$upload_dir}` 資料夾權限。</div>";
                    }
                    
                } elseif (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] !== UPLOAD_ERR_NO_FILE) {
                    // 處理單個檔案上傳時的其他錯誤
                    $all_files_ok = false;
                    $message .= "<div style='color: orange;'>⚠️ 檔案 **{$input_name}** 上傳失敗 (錯誤碼: {$_FILES[$input_name]['error']})</div>";
                } else {
                     // 該檔案欄位未選擇檔案
                     $all_files_ok = false;
                     $message .= "<div style='color: orange;'>⚠️ 檔案 **{$input_name}** 未選擇。</div>";
                }
            }
            
            if ($success_count === 2) {
                 $message = "<h3 style='color: green; text-align: center;'>🎉 兩張圖片皆成功上傳！</h3>" . $message;
            } elseif ($success_count > 0 && $success_count < 2) {
                 $message = "<h3 style='color: orange; text-align: center;'>部分圖片上傳成功。</h3>" . $message;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="FHZ.ico" type="image/x-icon">
    <title>FHZ賽事檔案上傳區</title>
    <style>
        /* 樣式優化，讓密碼區塊更突出 */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 8px 16px rgba(0,0,0,0.15); width: 100%; max-width: 550px; }
        h1 { color: #333; margin-bottom: 30px; font-size: 28px; text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group { margin-bottom: 20px; border: 1px solid #eee; padding: 15px; border-radius: 5px; border-left: 5px solid #007bff; }
        
        /* 密碼欄位特別樣式 */
        .password-group { border-left-color: #dc3545; background-color: #fef7f7; }
        
        input[type="date"], input[type="file"], input[type="password"] { 
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; margin-top: 5px; 
        }
        
        button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 18px; transition: background-color 0.3s; margin-top: 20px; }
        button:hover { background-color: #0056b3; }
        
        .result { margin-top: 25px; padding: 15px; border-radius: 5px; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .result h3 { margin-top: 0; color: #155724; }
        .result div { margin: 8px 0; padding-left: 10px; border-left: 3px solid #155724; }
        .result div[style*="color: red"] { color: #721c24 !important; background-color: #f8d7da; border-left-color: #dc3545; }
        .result div[style*="color: orange"] { color: #856404 !important; background-color: #fff3cd; border-left-color: #ffc107; }
        
        .hint { margin-top: 25px; font-size: 14px; color: #6c757d; border-top: 1px dashed #ced4da; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>直播圖片上傳中心</h1>
        
        <?php if ($message): ?>
            <div class="result">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            
            <div class="form-group password-group">
                <label for="password">🔒 密碼驗證：</label>
                <input type="password" id="password" name="password" placeholder="請輸入上傳密碼" required>
            </div>
            
            <div class="form-group" style="background-color: #e9f0ff;">
                <label for="eventDate">1. 選擇活動日期 (用於命名檔案)：</label>
                <input type="date" id="eventDate" name="event_date" value="<?php echo date('Y-m-d'); ?>" required>
                <small style="color: #6c757d;">例如選擇 2025/09/01，則檔名將以 20250901 開頭。</small>
            </div>
            
            <div class="form-group">
                <label for="coverImage">2. 上傳直播封面圖 (命名為：[日期]-1.jpg)</label>
                <input type="file" id="coverImage" name="cover_image" accept="image/*" required>
            </div>
            
            <div class="form-group">
                <label for="scoreImage">3. 上傳積分畫面圖 (命名為：[日期]-2.jpg)</label>
                <input type="file" id="scoreImage" name="score_image" accept="image/*" required>
            </div>
            
            <button type="submit">確認並上傳兩張圖片</button>
        </form>
        <div class="hint">
            <p><strong>🎯 上傳規則摘要：</strong></p>
            <ul>
                <li>檔案將儲存到 <code><?php echo $upload_dir; ?></code> 資料夾。</li>
                <li>所有檔案將強制轉換為 **.jpg** 副檔名。</li>
                <li>檔名範例：`20251116-1.jpg` 與 `20251116-2.jpg`。</li>
            </ul>
        </div>
    </div>
</body>
</html>