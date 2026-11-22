
# OBEDS Demo Script

## 1. Student A — Account and Book Listing
1. Student A registers and logs in.
2. Student A opens the List a Book page.
3. Student A lists a book named "Data Structures Book".

## 2. Student B — Search and Request
4. Student B logs in.
5. Student B searches for "Data Structures Book".
6. Student B opens the book details.
7. Student B sends a book request.

## 3. Student A — Manage Request
8. Student A opens Incoming Requests.
9. Student A views the Pending request.
10. Student A accepts or rejects the request.

## 4. Expected Results

### If Accepted
- Request status changes to Accepted.
- The book becomes unavailable.
- Other pending requests for that book are rejected.

### If Rejected
- Request status changes to Rejected.
- The book remains available.

## 5. Final Verification
- Check request status in My Requests.
- Check book availability in Browse Books.
- Confirm that both student accounts work correctly.
