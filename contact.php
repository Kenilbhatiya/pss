<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$name = "";
$email = "";
$subject = "";
$message = "";
$success_message = "";
$error_message = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // Include database connection
        include_once("includes/db_connection.php");
        
        // Prepare SQL statement to insert contact message
        $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Thank you for your message! We will get back to you soon.";
            // Clear form fields after successful submission
            $name = "";
            $email = "";
            $subject = "";
            $message = "";
        } else {
            $error_message = "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Contact Banner -->
    <section class="contact-banner py-5 bg-light">
        <div class="container text-center">
            <h1 class="display-4 fw-bold text-success">Contact Us</h1>
            <p class="lead">We'd love to hear from you! Get in touch with our team for any questions or feedback.</p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="contact-content py-5">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="mb-4">Send Us a Message</h2>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success mb-4">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger mb-4">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Your Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h3 class="card-title mb-4">Contact Information</h3>
                            <ul class="list-unstyled">
                                <li class="d-flex mb-4">
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Our Location</h5>
                                        <p class="mb-0">123 Green Street, Plant City, FL 34567</p>
                                    </div>
                                </li>
                                <li class="d-flex mb-4">
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Phone Number</h5>
                                        <p class="mb-0">+1 234 567 8901</p>
                                    </div>
                                </li>
                                <li class="d-flex mb-4">
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Email Address</h5>
                                        <p class="mb-0">info@plantnursery.com</p>
                                    </div>
                                </li>
                                <li class="d-flex">
                                    <div class="icon-circle bg-success text-white me-3">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Business Hours</h5>
                                        <p class="mb-0">Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="card-title mb-4">Connect With Us</h3>
                            <div class="social-media">
                                <a href="#" class="social-icon bg-facebook me-2"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon bg-twitter me-2"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon bg-instagram me-2"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-icon bg-pinterest"><i class="fab fa-pinterest-p"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2 class="text-center mb-4">Find Us On The Map</h2>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3525.7246118403256!2d-82.12788532414709!3d27.944185276697087!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x88c2cf407c79cbd5%3A0xa4af13f988ed2d0b!2sPlant%20City%2C%20FL%2033563!5e0!3m2!1sen!2sus!4v1650987654321!5m2!1sen!2sus" 
                                width="100%" height="450" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">Frequently Asked Questions</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="contactFaq">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How do I track my order?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#contactFaq">
                                <div class="accordion-body">
                                    You can track your order by logging into your account and visiting the "My Orders" section. Alternatively, you can use the tracking number provided in your shipping confirmation email.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#contactFaq">
                                <div class="accordion-body">
                                    We offer a 30-day guarantee on all our plants. If your plant arrives damaged or dies within 30 days of delivery, we'll replace it free of charge or provide a full refund.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Do you ship internationally?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#contactFaq">
                                <div class="accordion-body">
                                    Currently, we only ship within the United States. We're working on expanding our shipping options to include international destinations in the near future.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    How do I care for my new plant?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#contactFaq">
                                <div class="accordion-body">
                                    Each plant comes with a care guide specific to its species. You can also find detailed care information on our website or contact our plant experts for personalized advice.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add custom CSS for contact page -->
    <style>
        .icon-circle {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
            color: white;
        }
        
        .bg-facebook {
            background-color: #3b5998;
        }
        
        .bg-twitter {
            background-color: #1da1f2;
        }
        
        .bg-instagram {
            background-color: #e1306c;
        }
        
        .bg-pinterest {
            background-color: #bd081c;
        }
    </style>
</body>
</html> 