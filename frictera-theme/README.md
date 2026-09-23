# Frictera WordPress Theme

A custom block theme for the Frictera marketing website.

## Structure

```
frictera-theme/
├── style.css          # Theme stylesheet with design tokens
├── theme.json         # WordPress block theme configuration
├── functions.php      # Theme functions and setup
├── templates/
│   └── page-home.html # Homepage template
├── parts/
│   ├── header.html    # Header template part
│   └── footer.html    # Footer template part
├── patterns/          # Block patterns (empty for now)
└── assets/            # Theme assets (empty for now)
```

## Deployment to Hostinger

### Prerequisites

1. WordPress installed on Hostinger
2. Administrator access to WordPress admin
3. FTP/SFTP or File Manager access to Hostinger

### Deployment Steps

1. **Upload Theme**
   - Compress the `frictera-theme` directory to `frictera-theme.zip`
   - In WordPress admin: Appearance → Themes → Add New → Upload Theme
   - Choose `frictera-theme.zip` and click "Install Now"
   - **DO NOT activate yet**

2. **Verify Theme**
   - Go to Appearance → Themes
   - Confirm "Frictera" theme appears
   - Click "Live Preview" to verify appearance

3. **Activate Theme**
   - Once verified, click "Activate"

4. **Set Homepage**
   - Go to Settings → Reading
   - Set "Your homepage displays" to "A static page"
   - Create/select a page with the "Homepage" template

5. **Configure Navigation**
   - Go to Appearance → Editor
   - Edit the Header template part
   - Configure navigation menu items

### Rollback Plan

1. Go to Appearance → Themes
2. Activate "Twenty Twenty-Five" (or previous theme)
3. Delete "Frictera" theme if needed

## Development Workflow

1. Make changes in VS Code
2. Test locally with `python3 -m http.server`
3. Commit changes to Git
4. Upload updated theme to Hostinger

## Support

For issues, contact the development team.
