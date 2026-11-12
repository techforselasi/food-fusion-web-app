# FoodFusion Culinary Platform

**FoodFusion** is a modern, responsive web application designed for food lovers, home cooks, and culinary enthusiasts worldwide. It bridges the gap between individual recipe exploration and an interactive community cookbook, offering features ranging from secure authentication to cloud-backed media management and AI assistance.

---

## Key Features & Pages

* **Homepage (`index.php`):** Dynamic landing page featuring a promotional hero carousel, curated news feeds (integrated via external JSON APIs like TheMealDB), and quick links to culinary resources.
* **Recipe Collection (`recipe.php`):** A searchable and filterable database catalog allowing users to sort recipes by keyword, cuisine type, dietary preference, and difficulty level.
* **Community Cookbook (`community.php`):** An interactive hub where registered users can share their own recipes, complete with ingredients, step-by-step instructions, and nutritional information.
* **About Us (`about.php`):** Showcases the platform's culinary philosophy, core values (inclusivity, education, community, sustainability), and the student development team.
* **Culinary & Educational Resources (`culinary.php` / `resources.php`):** Downloadable references (PDF handbooks), technique guides, and embedded instructional videos (covering knife skills, food safety, and cooking fundamentals).
* **Contact & Location (`contact.php`):** Direct feedback messaging form connected to backend database logging, complete with an interactive embedded map.
* **AI Kitchen Assistant ("Fusion Assistant"):** An integrated chatbot interface powered via a secure backend proxy to assist users with cooking questions and recipe ideas.

---

## Tech Stack & Architecture

* **Backend:** PHP (strict types enforced) utilizing **PHP Data Objects (PDO)** for secure, parameterized database interactions.
* **Database:** MySQL relational database (`food_fusion_db`) consisting of 15+ normalized tables tracking users, recipes, steps, ingredients, nutrition, interactions, feedback, and security tokens.
* **Frontend:** HTML5, modern CSS3 (utilizing CSS Grid, Flexbox, and media queries for mobile-first responsiveness), and vanilla JavaScript.
* **Cloud & Media Integration:** **Cloudinary CDN** for optimized, cloud-based image storage and retrieval, bypassing local storage limitations.
* **Security Layer:** 
  * Password hashing via the **Argon2i** algorithm (`password_hash()`).
  * Brute-force protection featuring a 3-minute account lockout mechanism after 3 failed login attempts.
  * Prepared statements via PDO to prevent SQL injection vulnerabilities.
