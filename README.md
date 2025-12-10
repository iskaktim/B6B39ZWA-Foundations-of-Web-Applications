# **B6B39ZWA Foundations of Web Applications**

Winter Semester 2025 - Final Project for B6B39ZWA Foundations of Web Applications

## Discussion Forum

This is a web-based discussion forum that allows users to register, log in, create posts, upload images, comment, and manage their profiles.

The system supports regular users, administrators, and an owner role with extended permissions.

https://zwa.toad.cz/~iskaktim/

## **Features**

### **Authentication & Authorization**

* **Registration:** Users can register with a unique username and email. Password confirmation is required.
* **Login:** Users authenticate using a username and password.
* **Roles:**
  * **Guest:** Can browse posts and comments but cannot create content or access user-restricted pages.
  * **User:** Can create, edit, and delete their own posts and comments, and manage their profile settings.
  * **Admin:** Can delete any post or comment and promote users to admin.
  * **Owner:** Full system access — can delete any user, assign or revoke admin roles.

### Navigation

* Logged-in users can access: **Profile**, **Forum**, **My Posts, Add a Post** and **Logout**. (**Admin Panel** only for admin and owner roles)
* Not logged-in users can access: **Forum**, **Login** and **Registration**.

Navigation dynamically updates based on user authentication and role

### **User Profile**

* View personal information including username, email, role, avatar, and account creation date.
* Edit username, email, password, and avatar.
* Users can upload or remove their avatar (with a default avatar provided).

### **Post Management**

* **Add Post:** Authenticated users can create posts with title, content, and an optional image.
* **Edit Post:** Authors can edit the title, content, and replace/remove the image.
* **Delete Post:** Authors can delete their own posts; admins and owner can delete any post.
* Posts show title, content, author, image (if present), creation time, and last edited time.

### **Comments**

* Comments are visible to all users, including guests.
* Authenticated users can add comments under any post.
* Authors can edit and delete their own comments.
* Admins and owner can delete any comment.

### **Admin Panel**

* Accessible only to **admin** and **owner** roles.
* Displays all registered users with username, email, role, and total posts.
* **Owner** can promote/demote admins and delete any user.
* **Admin** can promote users to admin but cannot demote another admin.
* Both roles can delete users (except owner).

### **Pagination**

* Pagination is implemented on **Forum**, **My Posts**, and **Comments** pages.
* A new page appears automatically when more than **five** objects (posts or comments) exist.

### **File Uploads**

* Users can upload:
  * Avatars
  * Post images
* Server validates file type and stores uploaded files in dedicated directories.
* Default avatar is provided.

### **Access Control**

* Unauthorized users attempting to access protected pages are redirected to **login.html**.
* UI elements (action buttons) are hidden if the user does not have permissions to perform an action.
* Middleware ensures server-side permission checking for all restricted operations.

## **Files Overview**

### Architecture

The project follows the **MVC (Model–View–Controller)** architecture.

* **Models** handle all database operations.
* **Views** are represented by the client-side HTML, CSS, and JavaScript files used to display the forum interface.
* **Controllers** process requests, apply business logic, and return responses (mainly JSON).

### **Project Structure**

### **index.html**

The main entry page of the application. It loads the forum interface, initializes JavaScript logic, and displays the most recent posts.

### **client/**

Client-side part of the web application.\
Responsible for rendering the UI, handling user interactions, sending API requests, and updating content dynamically.

Contents:

* **html pages**
  * **login.html** - page for user authentication with username and password.
  * **register.html** - page for creating a new account.
  * **profile.html** - page for viewing and editing user profile information.
  * **my_posts.html** - page showing all posts created by the logged-in user.
  * **admin.html** - admin panel listing all users with role-management actions.
  * **detail_post.html** - detailed view of a single post with comments and actions.
  * **add_post.html** - page for creating new posts, including optional image upload.
  * **dashboard.html** - redirects unauthenticated users to Login/Register.
* **css/** - global and page-specific styles.
* **js/** - scripts for authentication, posts, comments, pagination, and shared UI functions.
* **uploads/** - user-uploaded avatars and post images.
* **images/** - static assets such as the default avatar.

### **server/**

Server-side part of the web application.\
Handles all API requests, authentication logic, permission checks, and database operations.

Contents:

* **controllers/** - request handling and API endpoints.
  * **auth_middleware.php** - login, admin, and owner permission checks.
  * **comment_controller.php** - CRUD operations for comments, pagination.
  * **post_controller.php** - CRUD operations for posts, pagination.
  * **user_controller.php** - registration, login, profile updates, avatar handling.
* **models/** - database abstraction layer.
  * **comment_model.php** - comment queries.
  * **post_model.php** - post queries, sorting.
  * **user_model.php** - user authentication and profile-related queries.
* **config.php** - initializes the backend, starts the session, and sets up the database connection.
* **avatar.php** - returns user avatar files or a default image if none is set.

## **Example Profiles**

### Owner

* **Username:** iskaktim
* **Password:** 1

### Admin

* **Username:** admin
* **Password:** admin1

### User

* **Username:** user
* **Password:** user11
* **Username:** user2
* **Password:** user22
