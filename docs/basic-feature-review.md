# Basic Feature Review

## Target

- WBS 6: Authentication and Book CRUD
- WBS 7: Review and Genre
- WBS 8: Favorites, Review Likes, and Ranking

## Review Date

2026-07-06

## Branch

main

## Git Status

working tree clean

## Test Result

- 22 passed
- 48 assertions
- PHP 8.5 deprecated warnings are currently ignored

## Pint Result

- PASS 90 files

## Completed Features

### Authentication

- Register
- Login
- Logout
- Redirect guests to login page

### Book CRUD

- Book list
- Book detail
- Book create
- Book edit
- Book delete
- Book and genre relation
- Only owner can edit or delete books

### Review

- Review create
- Review edit
- Review delete
- One review per user per book
- Rating 1 to 5
- Body max 1000 characters
- Only review owner can edit or delete reviews

### Genre

- Genre list
- Genre create
- Genre detail
- Genre edit
- Genre delete
- Cannot delete genres linked to books
- Genre name must be unique

### Favorite

- Add favorite
- Remove favorite
- Favorite book list
- Guests cannot access favorites

### Review Like

- Like review
- Unlike review
- Review like count
- Guests cannot like reviews

### Ranking

- Show reviewed books only
- Order by average rating
- If ratings are tied, order by review count
- Show top 10 books

## Reviewed Items

- Routes
- Auth middleware
- Migrations
- Model relationships
- FormRequest validation
- Policies
- Controllers
- Mojibake search in PHP and Blade files
- Feature tests
- Pint
- Git status

## Fixes During Review

### Genre validation attributes

- Fixed mojibake in StoreGenreRequest
- Fixed mojibake in UpdateGenreRequest
- Branch: feature/basic-review-fixes
- Merged into main

### Review flash messages

- Fixed mojibake in ReviewController flash messages
- Branch: feature/review-message-fixes
- Merged into main

## Remaining Tasks

- Update README
- Implement public API
- Check API abnormal cases
- Add API tests
- Add relation tests if needed
- Push to GitHub