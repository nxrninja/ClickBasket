-- ClickBasket E-commerce Categories Update Script
-- This script replaces existing categories with comprehensive e-commerce categories

USE clickbasket;

-- First, let's backup existing categories (optional)
-- CREATE TABLE categories_backup AS SELECT * FROM categories;

-- Clear existing categories (this will set category_id to NULL in products due to foreign key constraint)
DELETE FROM categories;

-- Reset auto increment
ALTER TABLE categories AUTO_INCREMENT = 1;

-- Insert comprehensive e-commerce categories
INSERT INTO categories (name, slug, description, is_active) VALUES

-- Fashion & Apparel
('Fashion & Apparel', 'fashion-apparel', 'Clothing, shoes, accessories, and fashion items for men, women, and children', 1),
('Men\'s Clothing', 'mens-clothing', 'Shirts, pants, suits, jackets, and men\'s fashion wear', 1),
('Women\'s Clothing', 'womens-clothing', 'Dresses, tops, bottoms, outerwear, and women\'s fashion', 1),
('Kids & Baby', 'kids-baby', 'Children\'s clothing, baby items, toys, and accessories', 1),
('Shoes & Footwear', 'shoes-footwear', 'Athletic shoes, dress shoes, boots, sandals, and footwear', 1),
('Bags & Accessories', 'bags-accessories', 'Handbags, wallets, jewelry, watches, and fashion accessories', 1),

-- Electronics & Technology
('Electronics', 'electronics', 'Consumer electronics, gadgets, and technology products', 1),
('Smartphones & Tablets', 'smartphones-tablets', 'Mobile phones, tablets, and mobile accessories', 1),
('Computers & Laptops', 'computers-laptops', 'Desktop computers, laptops, and computer accessories', 1),
('Audio & Headphones', 'audio-headphones', 'Speakers, headphones, earbuds, and audio equipment', 1),
('Gaming', 'gaming', 'Video games, gaming consoles, and gaming accessories', 1),
('Smart Home', 'smart-home', 'Smart devices, home automation, and IoT products', 1),

-- Home & Garden
('Home & Garden', 'home-garden', 'Home improvement, furniture, decor, and garden supplies', 1),
('Furniture', 'furniture', 'Living room, bedroom, office, and outdoor furniture', 1),
('Home Decor', 'home-decor', 'Wall art, lighting, rugs, curtains, and decorative items', 1),
('Kitchen & Dining', 'kitchen-dining', 'Cookware, appliances, dinnerware, and kitchen tools', 1),
('Garden & Outdoor', 'garden-outdoor', 'Gardening tools, outdoor furniture, and patio accessories', 1),
('Tools & Hardware', 'tools-hardware', 'Hand tools, power tools, and hardware supplies', 1),

-- Health & Beauty
('Health & Beauty', 'health-beauty', 'Personal care, cosmetics, and wellness products', 1),
('Skincare', 'skincare', 'Face care, body care, and skincare treatments', 1),
('Makeup & Cosmetics', 'makeup-cosmetics', 'Foundation, lipstick, eyeshadow, and beauty products', 1),
('Hair Care', 'hair-care', 'Shampoo, conditioner, styling products, and hair tools', 1),
('Health & Wellness', 'health-wellness', 'Vitamins, supplements, and health monitoring devices', 1),
('Fragrances', 'fragrances', 'Perfumes, colognes, and body sprays', 1),

-- Sports & Outdoors
('Sports & Outdoors', 'sports-outdoors', 'Athletic gear, outdoor equipment, and fitness products', 1),
('Fitness Equipment', 'fitness-equipment', 'Exercise machines, weights, and home gym equipment', 1),
('Outdoor Recreation', 'outdoor-recreation', 'Camping, hiking, fishing, and outdoor adventure gear', 1),
('Team Sports', 'team-sports', 'Equipment for football, basketball, soccer, and other team sports', 1),
('Water Sports', 'water-sports', 'Swimming, surfing, diving, and water activity equipment', 1),
('Athletic Apparel', 'athletic-apparel', 'Sportswear, activewear, and athletic shoes', 1),

-- Books & Media
('Books & Media', 'books-media', 'Books, movies, music, and educational content', 1),
('Books', 'books', 'Fiction, non-fiction, textbooks, and digital books', 1),
('Movies & TV', 'movies-tv', 'DVDs, Blu-rays, and digital video content', 1),
('Music', 'music', 'CDs, vinyl records, and digital music', 1),
('Video Games', 'video-games', 'Console games, PC games, and gaming software', 1),

-- Automotive
('Automotive', 'automotive', 'Car parts, accessories, and automotive supplies', 1),
('Car Electronics', 'car-electronics', 'GPS, dash cams, stereos, and car tech accessories', 1),
('Car Care', 'car-care', 'Cleaning supplies, maintenance products, and car care tools', 1),
('Car Accessories', 'car-accessories', 'Seat covers, floor mats, organizers, and interior accessories', 1),

-- Food & Beverages
('Food & Beverages', 'food-beverages', 'Gourmet foods, snacks, and specialty beverages', 1),
('Gourmet Food', 'gourmet-food', 'Premium foods, specialty ingredients, and artisanal products', 1),
('Snacks & Candy', 'snacks-candy', 'Chips, cookies, chocolate, and confectionery', 1),
('Beverages', 'beverages', 'Coffee, tea, soft drinks, and specialty beverages', 1),

-- Pet Supplies
('Pet Supplies', 'pet-supplies', 'Pet food, toys, accessories, and care products', 1),
('Dog Supplies', 'dog-supplies', 'Dog food, toys, leashes, and canine accessories', 1),
('Cat Supplies', 'cat-supplies', 'Cat food, litter, toys, and feline accessories', 1),
('Pet Health', 'pet-health', 'Pet medications, supplements, and health products', 1),

-- Office & Business
('Office & Business', 'office-business', 'Office supplies, business equipment, and professional tools', 1),
('Office Supplies', 'office-supplies', 'Pens, paper, folders, and general office materials', 1),
('Business Equipment', 'business-equipment', 'Printers, scanners, shredders, and office machines', 1),
('Professional Services', 'professional-services', 'Business consulting, software, and professional tools', 1),

-- Arts & Crafts
('Arts & Crafts', 'arts-crafts', 'Art supplies, craft materials, and creative tools', 1),
('Art Supplies', 'art-supplies', 'Paints, brushes, canvases, and drawing materials', 1),
('Craft Materials', 'craft-materials', 'Fabric, yarn, beads, and crafting supplies', 1),
('Sewing & Quilting', 'sewing-quilting', 'Sewing machines, fabric, patterns, and quilting supplies', 1),

-- Jewelry & Watches
('Jewelry & Watches', 'jewelry-watches', 'Fine jewelry, fashion jewelry, and timepieces', 1),
('Fine Jewelry', 'fine-jewelry', 'Gold, silver, diamonds, and precious stone jewelry', 1),
('Fashion Jewelry', 'fashion-jewelry', 'Costume jewelry, fashion accessories, and trendy pieces', 1),
('Watches', 'watches', 'Luxury watches, smart watches, and timepieces', 1),

-- Baby & Maternity
('Baby & Maternity', 'baby-maternity', 'Baby products, maternity wear, and parenting essentials', 1),
('Baby Gear', 'baby-gear', 'Strollers, car seats, high chairs, and baby equipment', 1),
('Baby Clothing', 'baby-clothing', 'Infant and toddler clothing, shoes, and accessories', 1),
('Maternity', 'maternity', 'Maternity clothing, nursing supplies, and pregnancy products', 1),

-- Toys & Games
('Toys & Games', 'toys-games', 'Children\'s toys, board games, and educational games', 1),
('Educational Toys', 'educational-toys', 'Learning toys, STEM toys, and developmental games', 1),
('Action Figures', 'action-figures', 'Collectible figures, dolls, and character toys', 1),
('Board Games', 'board-games', 'Family games, strategy games, and puzzle games', 1),

-- Digital Products (keeping some digital focus)
('Digital Products', 'digital-products', 'Software, digital downloads, and online services', 1),
('Software', 'software', 'Computer software, mobile apps, and digital tools', 1),
('Digital Art', 'digital-art', 'Digital graphics, templates, and design resources', 1),
('Online Courses', 'online-courses', 'Educational content, tutorials, and skill development', 1);

-- Update the schema comment
ALTER TABLE categories COMMENT = 'E-commerce product categories for comprehensive online store';
