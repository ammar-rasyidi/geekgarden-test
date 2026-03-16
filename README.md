# GeekGarden Fullstack Test Submission

## What’s Inside

### Section 1: Stock Logic (TypeScript)

<img width="707" height="514" alt="Image" src="https://github.com/user-attachments/assets/664b006b-86fd-43b6-b491-1fcfb956fd77" />

I wrote a function that tracks inventory in and out, and calculates what's left for each product.

**Files:**
- `src/stockCheck.ts` – main logic with all the types set up
- `src/stockCheck.test.ts` – tests for normal and edge cases

**My approach:**
- Used `reduce()` instead of a regular loop (just reads better to me)
- Set up TypeScript interfaces
- Tested for regular stuff and tested what happens with empty arrays, negative numbers, and products with only incoming stock

**Expected output:**
```
{ A: 6, B: 10, C: 50, D: 5, E: 10 }
```

<img width="803" height="591" alt="Image" src="https://github.com/user-attachments/assets/70038050-cbe6-47db-b1eb-8760867bb960" />


---

### Section 2: Laravel Order Service 

<img width="522" height="591" alt="Image" src="https://github.com/user-attachments/assets/8afecd31-4ad8-4f01-87be-3c3b254009db" />


**Issues that I noticed:**
- No database transaction, so half-failed orders could end up with broken data.
- Missing error handling
- Didn’t even check if the items array was empty
- Order number used to be timestamp-based, which could cause duplicates if multiple orders were created at the same time.
- No logging, so if something broke, it will hard to debug.
- Says it returns `Order`, but if it fails, what then?

**What I fixed:**
- Wrapped it all in `DB::transaction()` – it either works, or nothing gets saved
- Added try-catch with data logging so we will know what works and don't.
- Made sure there are actually items in the order before going any further
- Order number now includes a random string for better uniqueness.
- Return type is always consistent (success, message, order)
- Added tests for both success and failure

**Files:**
- `app/Services/OrderService.php` – my updated service
- `tests/Unit/Services/OrderServiceTest.php` – tests with logging

<img width="803" height="470" alt="Image" src="https://github.com/user-attachments/assets/3f37ae90-8680-4171-9ab3-99641915b4c2" />

---

## How to Run

### Section 1 (TypeScript)
```bash
npm install
npm test
```

### Section 2 (Laravel)
```bash
composer install
php artisan test
```

---
