<?php
$currentPage = 'contact';
$pageTitle = "Contact Us - Mendly";

// 读取重定向回来的状态 (Status Message)
$status = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/contact.css">
</head>

<body>

    <!-- Header 引入 -->
    <?php include 'includes/header.php'; ?>

    <main id="contact-main">
        <div class="contact-page-container">

            <!-- 居中大标题与副标题 -->
            <div class="page-header">
                <h1>Contact Us</h1>
            </div>

            <!-- 主内容 Container：左侧联系信息 + 右侧地图 -->
            <div class="contact-info-container">

                <!-- 左半边：业务联系渠道与社交媒体 -->
                <div class="contact-details">
                    <div class="info-group">
                        <h2>Our Location & Contact</h2>

                        <div class="contact-item">
                            <span class="info-icon">📍</span>
                            <div>
                                <strong>Address:</strong>
                                <p>123, Jalan Bukit Bintang, Bukit Bintang,<br>55100 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur, Malaysia</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <span class="info-icon">📞</span>
                            <div>
                                <strong>Phone & WhatsApp:</strong>
                                <p>+60 3-2123 4567 / +60 12-345 6789</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <span class="info-icon">✉️</span>
                            <div>
                                <strong>Email:</strong>
                                <p>support@mendly.my</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <span class="info-icon">⏰</span>
                            <div>
                                <strong>Operating Hours:</strong>
                                <p>Monday – Saturday: 9:00 AM – 6:00 PM<br>Sunday & Public Holidays: Closed</p>
                            </div>
                        </div>
                    </div>

                    <hr class="info-divider">

                    <!-- 社交媒体与账号名称列表 -->
                    <div class="social-media-group">
                        <h3>Connect With Us</h3>
                        <ul class="social-list">
                            <li>
                                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer">
                                    <img src="assets/images/facebook-colored.png" alt="Facebook" class="social-icon">
                                    <span class="social-name">@MendlyMalaysia</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer">
                                    <img src="assets/images/instagram-colored.png" alt="Instagram" class="social-icon">
                                    <span class="social-name">@mendly.official</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://xiaohongshu.com" target="_blank" rel="noopener noreferrer">
                                    <img src="assets/images/redNote-colored.png" alt="Red Note" class="social-icon">
                                    <span class="social-name">Mendly旧物修复</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://twitter.com" target="_blank" rel="noopener noreferrer">
                                    <img src="assets/images/x.png" alt="Twitter" class="social-icon">
                                    <span class="social-name">@mendly_my</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- 右半边：Google Embedded Map -->
                <div class="contact-map">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15935.267812984131!2d101.7032223!3d3.1478144!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc36284a1e3f87%3A0x62a8747f561917f8!2sBukit%20Bintang%2C%20Kuala%20Lumpur%2C%20Federal%20Territory%20of%20Kuala%20Lumpur!5e0!3m2!1sen!2smy!4v1700000000000!5m2!1sen!2smy"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>

            </div>

            <!-- 询盘表单区域 -->
            <div class="user-inquiry-form-wrapper">
                <h1>Inquiry Form</h1>
                <p>Have questions about our repair services or need assistance? We're here to help.</p>

                <!-- 提交成功/失败的动态反馈提示 -->
                <?php if ($status === 'success'): ?>
                    <div class="form-alert alert-success" style="padding: 12px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px;">
                        Thank you for reaching out! Your inquiry has been submitted successfully. We will contact you soon.
                    </div>
                <?php elseif ($status === 'empty'): ?>
                    <div class="form-alert alert-error" style="padding: 12px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 20px;">
                        Please fill in all required fields before submitting.
                    </div>
                <?php elseif ($status === 'error'): ?>
                    <div class="form-alert alert-error" style="padding: 12px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 20px;">
                        Something went wrong while sending your inquiry. Please try again later.
                    </div>
                <?php endif; ?>

                <!-- 💡 关键修改：action 设为 process-inquiry.php -->
                <form action="process-inquiry.php" method="POST" class="inquiry-form">

                    <!-- Row 1: Full Name -->
                    <div class="form-group full-width">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" placeholder="John Doe" required>
                    </div>

                    <!-- Row 2: Email Address -->
                    <div class="form-group full-width">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" placeholder="johndoe@example.com" required>
                    </div>

                    <!-- Row 3: Inquiry Category Dropdown (💡 关键修改: name="subject_type", value 为大写) -->
                    <div class="form-group full-width">
                        <label for="subject_type">Inquiry Category <span class="required">*</span></label>
                        <select id="subject_type" name="subject_type" required>
                            <option value="" disabled selected>Please select...</option>
                            <option value="Suggestion">Suggestion</option>
                            <option value="Complaint">Complaint</option>
                            <option value="Question">Question</option>
                        </select>
                    </div>

                    <!-- Row 4: Message Area -->
                    <div class="form-group full-width">
                        <label for="message">Message / Inquiry Details <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="6" placeholder="Please describe your enquiry or concern in detail..." required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-submit-wrapper">
                        <button type="submit" name="submit_inquiry" class="btn-submit">Submit Inquiry</button>
                    </div>

                </form>

            </div>

        </div>
    </main>

    <!-- Footer 引入 -->
    <?php include 'includes/footer.php'; ?>

</body>

</html>