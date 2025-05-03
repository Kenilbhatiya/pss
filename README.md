# Plant Nursery - Plant Selling System

Plant Nursery is a dynamic web-based plant selling system built with PHP, MySQL, and Bootstrap. It provides an attractive and functional e-commerce platform for selling plants online.

## Features

- Responsive, mobile-friendly design
- Dynamic product listings pulled from database
- Featured products section
- Category-based browsing
- User registration and authentication
- Shopping cart functionality
- Wishlist for saving favorite items
- Customer testimonials
- Newsletter subscription
- Order tracking

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/LAMP for local development

## Installation

1. Clone this repository to your local environment:
   ```
   git clone https://github.com/yourusername/plant-nursery-shop.git
   ```
   
2. Copy all files to your web server directory (e.g., `htdocs` folder in XAMPP)

3. Create a new MySQL database named `plant_shop` (or use phpMyAdmin to create it)

4. Import the database structure by running the SQL script:
   ```
   mysql -u root -p plant_shop < database_setup.sql
   ```
   
   Alternatively, you can import the `database_setup.sql` file through phpMyAdmin.

5. Configure the database connection in `includes/db_connection.php` if your database credentials differ from the default.

6. Ensure the `images` directory and its subdirectories have appropriate write permissions.

7. Access the website through your web browser (e.g., `http://localhost/pss`)

## Directory Structure

```
/
├── css/                  - CSS stylesheets
├── images/               - Image assets
│   ├── products/         - Product images
│   ├── categories/       - Category images
│   ├── testimonials/     - Testimonial images
│   └── banners/          - Banner images
├── includes/             - PHP includes and components
│   ├── header.php        - Site header
│   ├── footer.php        - Site footer
│   └── db_connection.php - Database connection
├── index.php             - Homepage
├── shop.php              - Product listing page
├── product.php           - Individual product page
├── category.php          - Category listing page
├── cart.php              - Shopping cart
├── wishlist.php          - User wishlist
├── login.php             - User login
├── register.php          - User registration
├── about.php             - About us page
├── contact.php           - Contact page
├── database_setup.sql    - Database structure and sample data
└── README.md             - This file
```

## Adding Your Own Images

Replace the placeholder images in the `images` directory with your own:

1. Add product images to `images/products/`
2. Add category images to `images/categories/`
3. Add testimonial profile images to `images/testimonials/`
4. Add a hero banner image named `hero-plant.jpg` to the `images` directory

## Customization

- Edit `css/style.css` to change the website's appearance
- Modify the color scheme by updating the CSS variables in the `:root` section
- Add/edit products and categories through the database

## Admin Panel

An admin panel for managing products, orders, and users is planned for future development.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Credits

- Bootstrap - https://getbootstrap.com/
- Font Awesome - https://fontawesome.com/
- Google Fonts - https://fonts.google.com/ 