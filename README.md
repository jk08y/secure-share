# SecureShare 🛡️

A secure, self-hosted file sharing solution with automatic file expiration and encryption. Upload files easily and share them with secure, time-limited download links.

## Demo
### Home page
![Home Page](https://raw.githubusercontent.com/jk08y/secure-share/refs/heads/main/screenshots/Screenshot%20from%202024-11-18%2000-50-26.png)

### Link Generation page (Loaded after upoading)

![After Uploading](https://raw.githubusercontent.com/jk08y/secure-share/refs/heads/main/screenshots/Screenshot%20from%202024-11-18%2000-50-34.png)

### File Download Page
- Includes a feature to preview the file youre about to download. Size,expiry, date and preview

![Download Page](https://raw.githubusercontent.com/jk08y/secure-share/refs/heads/main/screenshots/Screenshot%20from%202024-11-18%2000-51-00.png)

## Features

-  Secure file sharing with automatic encryption
-  Automatic file expiration (24 hours, 7 days, or 30 days)
-  File upload progress tracking
-  Drag and drop file upload interface
-  Detailed file preview page
-  Responsive design
-  Secure download links
-  Download tracking

## Supported File Types

- Images: JPG, PNG, JPEG
- Documents: PDF, DOC, DOCX
- Archives: ZIP, RAR

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO PHP Extension
- FileInfo PHP Extension

## Installation

1. Clone the repository:
```bash
git clone https://github.com/jk08y/secure-share.git
cd secure-share
```

2. Create a MySQL database and update the configuration in `config.php`:
```php
const DB_CONFIG = [
    'host' => 'localhost',
    'name' => 'your_database_name',
    'user' => 'your_database_user',
    'pass' => 'your_database_password'
];
```

3. Set up the uploads directory and permissions:
```bash
mkdir uploads
chmod 755 uploads
```

4. Configure your web server to point to the project directory.

5. Access the application through your web browser.

## Security Configuration

1. Ensure your `uploads` directory is properly secured:
```bash
# Set proper ownership
chown www-data:www-data uploads

# Set secure permissions (755 is recommended over 777 for better security)
chmod 755 uploads
```

2. Configure maximum upload size in both PHP and application settings:
   - Update `php.ini`: `upload_max_filesize` and `post_max_size`
   - Adjust `max_file_size` in `config.php`

3. Update your webserver configuration to prevent direct access to the uploads directory.

## Usage

1. Upload a file by dragging and dropping or clicking the upload area
2. Select an expiration time (24 hours, 7 days, or 30 days)
3. Get a secure sharing link
4. Share the link with your recipient

## Configuration Options

Edit `config.php` to customize:
- Maximum file size
- Allowed file extensions
- Upload directory path
- Database settings
- Expiration options
- Base URL  

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security Considerations

- Files are automatically deleted upon expiration
- Download links are randomly generated and secured
- File extensions are validated
- Maximum file size is enforced
- Database queries are prepared to prevent SQL injection

### License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### Author

-  𝕏: [@jk08y](https://x.com/jk08y)
- GitHub: [@jk08y](https://github.com/jk08y)
