<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Hero Banner -->
    <section class="about-hero py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold text-success">Our Story</h1>
                    <p class="lead">We are passionate about bringing nature into your living spaces with our carefully curated plant collection.</p>
                </div>
                <div class="col-md-6">
                    <img src="images/our nursery.jpg" alt="Our Nursery" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Our Mission -->
    <section class="mission-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">Our Mission</h2>
                    <p class="lead">At Plant Nursery, our mission is to help people transform their homes and workplaces into green sanctuaries. We believe that surrounding ourselves with plants improves our well-being, productivity, and connection to nature.</p>
                    <p>We are committed to providing healthy, high-quality plants along with the knowledge and support needed to help them thrive in your space.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Journey -->
    <section class="journey-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <img src="images/our journey.jpg" alt="Our Journey" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-6">
                    <h2 class="mb-4">Our Journey</h2>
                    <p>Plant Nursery began in 2015 as a small backyard passion project by our founder, Sarah Thompson. What started with a few potted plants and a love for gardening quickly grew into a thriving business.</p>
                    <p>Over the years, we've expanded our collection to include rare and exotic plants, created a dedicated team of plant experts, and built a community of plant enthusiasts who share our passion for greenery.</p>
                    <p>Today, we're proud to be a leading online plant nursery, shipping beautiful, healthy plants to customers nationwide. Our journey continues as we grow and evolve, always staying true to our roots and our love for all things green.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team -->
    <section class="team-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">Meet Our Team</h2>
            <div class="row" itemscope itemtype="http://schema.org/Organization">
                <div class="col-md-3 mb-4" itemprop="employee" itemscope itemtype="http://schema.org/Person">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="images/sarah thompson.jpg" class="card-img-top" alt="Sarah Thompson" itemprop="image">
                        <div class="card-body text-center">
                            <h5 class="card-title" itemprop="name">Sarah Thompson</h5>
                            <p class="card-text text-muted" itemprop="jobTitle">Founder & CEO</p>
                            <p class="card-text" itemprop="description">Plant enthusiast with over 15 years of experience in horticulture. Sarah oversees all aspects of the business with a special focus on plant curation.</p>
                            <div class="social-icons mt-3">
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-dark" itemprop="sameAs"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4" itemprop="employee" itemscope itemtype="http://schema.org/Person">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="images/michael rodriguez.jpg" class="card-img-top" alt="Michael Rodriguez" itemprop="image">
                        <div class="card-body text-center">
                            <h5 class="card-title" itemprop="name">Michael Rodriguez</h5>
                            <p class="card-text text-muted" itemprop="jobTitle">Head Horticulturist</p>
                            <p class="card-text" itemprop="description">With a degree in botany and a passion for rare plants, Michael leads our growing team and ensures all plants meet our quality standards.</p>
                            <div class="social-icons mt-3">
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-dark" itemprop="sameAs"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4" itemprop="employee" itemscope itemtype="http://schema.org/Person">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="images/emily chen.jpg" class="card-img-top" alt="Emily Chen" itemprop="image">
                        <div class="card-body text-center">
                            <h5 class="card-title" itemprop="name">Emily Chen</h5>
                            <p class="card-text text-muted" itemprop="jobTitle">Customer Experience Manager</p>
                            <p class="card-text" itemprop="description">Emily ensures our customers have an amazing experience, from browsing our website to receiving their plants and beyond.</p>
                            <div class="social-icons mt-3">
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-dark" itemprop="sameAs"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4" itemprop="employee" itemscope itemtype="http://schema.org/Person">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="images/david wilson.jpg" class="card-img-top" alt="David Wilson" itemprop="image">
                        <div class="card-body text-center">
                            <h5 class="card-title" itemprop="name">David Wilson</h5>
                            <p class="card-text text-muted" itemprop="jobTitle">Supply Chain Manager</p>
                            <p class="card-text" itemprop="description">David ensures that our plants are sourced sustainably and delivered safely to your doorstep with minimal environmental impact.</p>
                            <div class="social-icons mt-3">
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-dark me-2" itemprop="sameAs"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-dark" itemprop="sameAs"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="values-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Our Values</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-success text-white mx-auto mb-4">
                                <i class="fas fa-leaf fa-2x"></i>
                            </div>
                            <h4 class="card-title">Sustainability</h4>
                            <p class="card-text">We are committed to sustainable practices in our nursery and packaging. We use eco-friendly materials and minimize waste wherever possible.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-success text-white mx-auto mb-4">
                                <i class="fas fa-heart fa-2x"></i>
                            </div>
                            <h4 class="card-title">Quality</h4>
                            <p class="card-text">We take pride in providing healthy, vibrant plants. Each plant is carefully grown, selected, and inspected before being shipped to your door.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-success text-white mx-auto mb-4">
                                <i class="fas fa-handshake fa-2x"></i>
                            </div>
                            <h4 class="card-title">Customer Care</h4>
                            <p class="card-text">We're dedicated to providing exceptional customer service and supporting you on your plant journey with care guides and responsive support.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5 bg-success text-white">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Start Your Plant Journey?</h2>
            <p class="lead mb-4">Browse our collection and find the perfect plants for your space.</p>
            <a href="shop.php" class="btn btn-light btn-lg px-5">Shop Now</a>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 