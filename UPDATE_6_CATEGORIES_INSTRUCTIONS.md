# ClickBasket 6 Categories Update

## Overview
This update replaces all existing categories with 6 specific e-commerce categories as requested:

1. **Fashion** - Clothing, accessories, and fashion items
2. **Mobile** - Smartphones, tablets, and mobile accessories  
3. **Beauty** - Cosmetics, skincare, and beauty products
4. **Electronics** - Consumer electronics and gadgets
5. **Toys** - Toys and games for all ages
6. **Furniture** - Home and office furniture

## How to Update

### Method 1: Using Web Interface (Recommended)
1. **Backup your database first!**
2. Login to admin panel
3. Navigate to: `http://your-site.com/admin/update_simple_categories.php`
4. Review the 6 categories preview
5. Check the confirmation box
6. Click "Update to 6 Categories"

### Method 2: Using SQL Script
1. **Backup your database first!**
2. Run the SQL file: `database/update_6_categories.sql`
3. Execute in phpMyAdmin or MySQL command line

## What's Updated

### Database Changes:
- All existing categories replaced with 6 new ones
- Automatic backup created before update
- Products will need category reassignment

### User Interface Changes:
- Updated category icons for new categories
- Updated meta descriptions for e-commerce focus
- Category page descriptions updated
- Icon mappings for Fashion, Mobile, Beauty, Electronics, Toys, Furniture

### Category Icons:
- Fashion: T-shirt icon
- Mobile: Mobile phone icon  
- Beauty: Palette icon
- Electronics: Laptop icon
- Toys: Gamepad icon
- Furniture: Couch icon

## After Update

1. **Reassign Products**: Go to admin panel and assign existing products to new categories
2. **Test Categories**: Visit `/categories.php` to see the new layout
3. **Update Navigation**: Categories will automatically appear in navigation
4. **Add Products**: Start adding products to the new categories

## Files Modified:
- `admin/update_simple_categories.php` - Update interface
- `database/update_6_categories.sql` - SQL script
- `categories.php` - Updated icons and descriptions
- `includes/header.php` - Updated meta descriptions

## Rollback:
If needed, restore from the automatic backup table created during update.

The update is safe and includes automatic backup functionality.
