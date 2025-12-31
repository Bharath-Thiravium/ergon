Manual Verification Checklist

1. Setup
- Ensure web server and PHP have write access to `storage/receipts/` and `storage/proofs/`.
- Ensure the application connects to your test database.

2. Expense claim flow (user)
- Submit an expense at `/ergon/expenses/create` with a receipt.
- Confirm record exists in `expenses` table with `status = 'pending'`.

3. Approve expense (admin)
- Open `/ergon/expenses/view/{id}` as admin/owner.
- Enter an approved amount and click Approve.
- Confirm a row is inserted into `approved_expenses` with `expense_id` referencing the claim.
- If category = `deduct_from_advance`, confirm `expenses.status = 'paid'` and `approved_expenses.paid_at` is set and ledger entry created.

4. Mark paid with proof (admin)
- For a non-deducted approved expense, use Upload Proof -> choose file (jpg/png/pdf) and click Mark Paid.
- Confirm proof saved in `storage/proofs/` and `expenses.payment_proof` updated.
- Confirm `approved_expenses.payment_proof` and `paid_at` updated.
- Confirm ledger entry created (debit) for the user and balance reflects deductions.

5. Advance payment
- Approve an advance and then Mark Paid with proof.
- Confirm proof saved and `advances.payment_proof` updated.
- Confirm ledger entry created (credit) for the user and balance updated.

6. Admin listing
- Visit `/ergon/approved-expenses` as admin/owner.
- Use filters: status=paid/unpaid and Deducted checkbox to narrow results.
- Confirm results match DB values.

7. File validation
- Try to upload a file >5MB or of type not in [jpg,png,pdf] and confirm the server rejects with an error message.

8. Tests
- Run PHPUnit tests: `vendor/bin/phpunit --colors=always` from project root.
- Verify `tests/LedgerHelperTest.php` passes (requires DB connection).

Notes
- The test creates temporary ledger entries for user id 999999 and cleans them up after run.
- If you prefer different allowed MIME types or size limit, modify the controllers accordingly.
