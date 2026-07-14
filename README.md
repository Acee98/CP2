# ZPGC-Services-Capstone-2-Project | Prototyping

Prerquisites:
- Install XAMPP
- Fork this project to "File Explorer -> "C:" Drive -> xampp -> htdocs"

Steup-by-step setup:
- Open XAMPP Control Panel
- Start "Apache" and "MySQL" modules
  <img width="663" height="433" alt="image" src="https://github.com/user-attachments/assets/b73f9720-7def-42d8-982f-c1279a148e8d" />
  
- Open chrome and search for "localhost/phpmyadmin"
  <img width="769" height="32" alt="image" src="https://github.com/user-attachments/assets/371b00c5-6a60-4e64-bf7d-17b379e13bb2" />
  
- Create a database by clicking "Database in the top ribbon
  <img width="1364" height="680" alt="image" src="https://github.com/user-attachments/assets/f768eaf4-6a0d-4d05-9d50-bba8a6fd0dae" />
  
- Input the database name "users_db" and click create:
  <img width="1076" height="117" alt="image" src="https://github.com/user-attachments/assets/dc0315e5-0ac9-43b2-9e7c-bfc79a09f86a" />

- Set the name of the table to "users" and number of columns to "6"
- Set the names, types, length values, attributes, null index, and auto increment; PS:Set the null index of id to "PRIMARY", then click go. And set the null index of email to "UNIQUE", then click go. 
- 
<img width="1365" height="646" alt="image" src="https://github.com/user-attachments/assets/e1203b3b-2d71-4dd9-84a7-3ec62fedb217" />

<img width="1363" height="641" alt="image" src="https://github.com/user-attachments/assets/d3ca11d6-9734-49ce-b7e5-c0ad015b8bf6" />


<img width="1363" height="642" alt="image" src="https://github.com/user-attachments/assets/fd121382-545e-4e01-a7e6-690ba967da6b" />

Usage:

- Open chrome and search "http://localhost/CP2/pages/landing_page.php".
- Open a new tab and searh for "localhost/phpmyadmin" in the search bar.
- In phpmyadmin, at the sidebar, click "users_db -> users" to check for newly signed in users, admin, technician.
- Go back to the landing page and test out the results.
