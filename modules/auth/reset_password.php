<?php
// Password reset placeholder
?>
<?php include '../../includes/header.php'; ?>
<div class="container mt-5" style="max-width: 400px;">
    <h2 class="mb-4 text-center">Reset Password</h2>
    <div class="alert alert-info">Password reset feature coming soon.</div>
    <form method="post" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" required disabled>
        </div>
        <button type="submit" class="btn btn-primary w-100" disabled>Send Reset Link</button>
    </form>
    <div class="mt-3 text-center">
        <a href="login.php">Back to Login</a>
    </div>
</div>
<?php include '../../includes/footer.php'; ?> 