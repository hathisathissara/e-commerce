# 🎁 GiftSphere: The Perfect Gift for Every Occasion

GiftSphere is a full-featured e-commerce website built with PHP and Bootstrap, designed to make finding and sending the perfect gift a delightful experience. Users can browse a curated collection of gifts, filter by occasion or recipient, and add personal touches to make their present truly special.

---

## 📖 Table of Contents

- [About The Project](#-about-the-project)
- [Key Features](#-key-features)
- [Tech Stack](#-built-with)
- [Getting Started](#-getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation & Setup](#installation--setup)
- [Usage](#-usage)
- [Database Schema](#-database-schema)
- [Project Structure](#-project-structure)
- [License](#-license)
- [Contact](#-contact)

---

## 🎉 About The Project

The goal of GiftSphere is to provide a user-friendly and visually appealing platform for online gift shopping. Built on a classic server-side stack (PHP/MySQL), it demonstrates essential e-commerce functionalities tailored to the gifting market. The project emphasizes intuitive navigation and features like categorization by occasion (e.g., Birthdays, Anniversaries) and recipient (e.g., For Him, For Her), which are critical for a great gifting experience.

---

## ✨ Key Features

### For Customers:
*   **Secure User Accounts:** Easy registration and login process using PHP sessions for a persistent experience.
*   **Intuitive Gift Discovery:**
    *   **Search Functionality:** Quickly find specific items.
    *   **Categorization:** Browse gifts by type (e.g., Gadgets, Home Decor).
    *   **Filtering:** Narrow down choices by **Occasion** (Birthdays, Holidays) and **Recipient** (Him, Her, Kids).
*   **Product Personalization:** Options to add a custom gift message or select gift wrapping at checkout.
*   **Dynamic Shopping Cart:** Add, remove, and update item quantities seamlessly.
*   **User Wishlist:** Save favorite items for future purchase or to share with others.
*   **Order History:** Keep track of all past purchases in a personal dashboard.
*   **Responsive Design:** A beautiful and functional experience on any device, thanks to Bootstrap.

### For Admins:
*   **Secure Admin Panel:** A protected dashboard for all store management tasks.
*   **Product Management (CRUD):** Add new gifts, update existing details, and remove discontinued items.
*   **Category Management:** Easily manage the occasions, recipients, and product categories available for filtering.
*   **Order Fulfillment:** View incoming orders, update their status (e.g., "Processing," "Shipped"), and manage customer details.
*   **User Management:** View a complete list of all registered customers.

---

## 🛠️ Built With

This project is built with a reliable and widely-used tech stack:

**Frontend:**
*   [**HTML5 & CSS3**](https://developer.mozilla.org/en-US/docs/Web)
*   [**Bootstrap**](https://getbootstrap.com/) - For responsive design and pre-styled components.
*   [**JavaScript / jQuery**](https://jquery.com/) - For enhancing user interactivity.

**Backend:**
*   [**PHP**](https://www.php.net/) - For all server-side logic and page rendering.
*   [**MySQL**](https://www.mysql.com/) - The relational database for storing all application data.

**Development Environment:**
*   [**XAMPP / WAMP**](https://www.apachefriends.org/) - Local server stack for Apache, MySQL, and PHP.

---

## ⚙️ Getting Started

To get a local copy up and running, follow these simple steps.

### Prerequisites

You need a local server environment capable of running PHP and MySQL.
*   **XAMPP** is a popular choice for all operating systems. Download it from the [official website](https://www.apachefriends.org/index.html).

### Installation & Setup

1.  **Clone the repository:**
    Clone this project into your local server's web directory.
    *   For **XAMPP**, this is the `htdocs` folder (e.g., `C:\xampp\htdocs`).
    ```sh
    git clone https://github.com/hathisathissara/e-commerce C:/xampp/htdocs/giftsphere
    ```

2.  **Start your server:**
    Open the XAMPP Control Panel and start the **Apache** and **MySQL** services.

3.  **Create and Import the Database:**
    a. Open your browser and go to `http://localhost/phpmyadmin/`.
    b. Create a new, empty database. Let's name it `giftsphere_db`.
    c. Select the new database and click the **Import** tab.
    d. Click "Choose File" and select the `.sql` file included in this project (e.g., `database/giftsphere_db.sql`).
    e. Click "Go" at the bottom of the page to execute the import.

4.  **Configure Database Connection:**
    a. Locate the database configuration file in the project (e.g., `includes/config.php` or `db_connection.php`).
    b. Open it and update the credentials to match your local setup.
    ```php
    <?php
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');   // Default XAMPP username
    define('DB_PASSWORD', '');       // Default XAMPP password
    define('DB_NAME', 'giftsphere_db'); // The database name from the previous step
    ?>
    ```

5.  **Ready to Launch!**
    Open your browser and navigate to `http://localhost/giftsphere`.

---

## 👨‍💻 Usage

*   Navigate to `http://localhost/giftsphere` to start browsing.
*   Register a new customer account or log in with sample credentials.
*   **Admin User:** `admin@giftsphere.com` / `adminpass`
*   **Customer User:** `customer@example.com` / `userpass`
*   Test the core features: add items to the cart, save a product to your wishlist, and simulate a checkout.
*   Log in as an admin to see the management dashboard.

---

## 🗄️ Database Schema

The core database tables include:

| Table        | Description                                                  |
|--------------|--------------------------------------------------------------|
| `users`      | Stores customer and admin information, credentials, and roles. |
| `products`   | Stores all gift details: name, description, price, image URL. |
| `categories` | Stores different categories, occasions, and recipient types. |
| `orders`     | Contains header information for each order placed by a user. |
| `order_items`| A linking table detailing which products are in which order. |
| `wishlists`  | A linking table connecting users to the products they've saved.|

---

## 🌳 Project Structure
