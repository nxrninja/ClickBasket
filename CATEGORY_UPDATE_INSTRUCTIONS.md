# ClickBasket E-commerce Categories Update

## Overview
This update converts ClickBasket from a digital products store to a comprehensive e-commerce platform by replacing the existing 5 categories with 64 comprehensive e-commerce categories.

## What's Changed

### Current Categories (5):
- Web Templates
- Mobile Apps
- Graphics & Design
- Software Tools
- E-books

### New E-commerce Categories (64):
The new structure includes comprehensive categories for:

1. **Fashion & Apparel** (6 categories)
   - Fashion & Apparel, Men's Clothing, Women's Clothing, Kids & Baby, Shoes & Footwear, Bags & Accessories

2. **Electronics & Technology** (6 categories)
   - Electronics, Smartphones & Tablets, Computers & Laptops, Audio & Headphones, Gaming, Smart Home

3. **Home & Garden** (6 categories)
   - Home & Garden, Furniture, Home Decor, Kitchen & Dining, Garden & Outdoor, Tools & Hardware

4. **Health & Beauty** (6 categories)
   - Health & Beauty, Skincare, Makeup & Cosmetics, Hair Care, Health & Wellness, Fragrances

5. **Sports & Outdoors** (6 categories)
   - Sports & Outdoors, Fitness Equipment, Outdoor Recreation, Team Sports, Water Sports, Athletic Apparel

6. **Books & Media** (5 categories)
   - Books & Media, Books, Movies & TV, Music, Video Games

7. **Automotive** (4 categories)
   - Automotive, Car Electronics, Car Care, Car Accessories

8. **Food & Beverages** (4 categories)
   - Food & Beverages, Gourmet Food, Snacks & Candy, Beverages

9. **Pet Supplies** (4 categories)
   - Pet Supplies, Dog Supplies, Cat Supplies, Pet Health

10. **Office & Business** (4 categories)
    - Office & Business, Office Supplies, Business Equipment, Professional Services

11. **Arts & Crafts** (4 categories)
    - Arts & Crafts, Art Supplies, Craft Materials, Sewing & Quilting

12. **Jewelry & Watches** (4 categories)
    - Jewelry & Watches, Fine Jewelry, Fashion Jewelry, Watches

13. **Baby & Maternity** (4 categories)
    - Baby & Maternity, Baby Gear, Baby Clothing, Maternity

14. **Toys & Games** (4 categories)
    - Toys & Games, Educational Toys, Action Figures, Board Games

15. **Digital Products** (4 categories)
    - Digital Products, Software, Digital Art, Online Courses

## How to Update

### Method 1: Using the Web Interface (Recommended)
1. **Backup your database first!**
2. Login to admin panel as super admin
3. Navigate to: `http://your-site.com/admin/update_categories.php`
4. Review the category preview
5. Check the confirmation boxes
6. Click "Update Categories to E-commerce"

### Method 2: Using SQL directly
1. **Backup your database first!**
2. Run the SQL script: `database/update_ecommerce_categories.sql`
3. Execute in phpMyAdmin or MySQL command line

## Important Notes

### ⚠️ Before Running the Update:
1. **BACKUP YOUR DATABASE** - This is crucial!
2. Ensure you're logged in as super admin
3. Note that existing products will have their category associations removed
4. You'll need to reassign products to new categories after the update

### After the Update:
1. Review all categories in the admin panel
2. Reassign existing products to appropriate new categories
3. Update your website's category navigation if needed
4. Test the category functionality

### Safety Features:
- Automatic backup of existing categories (creates `categories_backup_YYYY_MM_DD_HH_MM_SS` table)
- Transaction-based update (rolls back on error)
- Super admin access required
- Confirmation checkboxes required

## Files Created:
- `admin/update_categories.php` - Web interface for updating
- `database/update_ecommerce_categories.sql` - SQL script
- `CATEGORY_UPDATE_INSTRUCTIONS.md` - This instruction file

## Rollback Instructions:
If you need to rollback:
1. Find your backup table: `categories_backup_YYYY_MM_DD_HH_MM_SS`
2. Run: `DELETE FROM categories; INSERT INTO categories SELECT * FROM categories_backup_YYYY_MM_DD_HH_MM_SS;`
3. Update product category associations as needed

## Support:
If you encounter any issues during the update process, check:
1. Database connection settings
2. Admin user permissions
3. PHP error logs
4. MySQL error logs

The update process is designed to be safe and reversible, but always backup first!
