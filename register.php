<?php 
include_once("header.php");
require 'database.php';

$categories = [];
$categoryQuery = "SELECT categoryID, categoryType FROM categories WHERE categoryID != 8;";
$categoryResults = mysqli_query($connection, $categoryQuery);
while ($row = mysqli_fetch_assoc($categoryResults)) {
  $categories[] = [
    'id' => $row['categoryID'],
    'type' => $row['categoryType']
  ];
}

if (isset($_SESSION['errors'])) {
  $errors = $_SESSION['errors'];
} else {
  $errors = []; 
}

if (isset($_SESSION['formData'])) {
  $formData = $_SESSION['formData'];
} else {
  $formData = []; 
}

if (isset($_SESSION['successMessage'])) {
  $successMessage = $_SESSION['successMessage'];
} else {
  $successMessage = '';
}
?>

<div class="container">
<h2 class="my-3">Register new account</h2>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
    </ul>
  </div>
<?php endif;?>

<?php if($successMessage): ?>
  <div class="alert alert-success">
    <p><?= htmlspecialchars($successMessage) ?></p>
  </div>
<?php endif;?>

<?php unset($_SESSION['errors'], $_SESSION['formData'], $_SESSION['successMessage']);?>

<!-- Create auction form -->
<form method="POST" action="process_registration.php">
  <div class="form-group row">
    <label for="accountType" class="col-sm-2 col-form-label text-right">Role</label>
    <div class="col-sm-10">
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="accountType" id="accountBuyer" value="buyer" <?= isset($formData['accountType']) && $formData['accountType'] === 'buyer' ? 'checked' : ''; ?>>
        <label class="form-check-label" for="accountBuyer">Buyer</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="accountType" id="accountSeller" value="seller" <?= isset($formData['accountType']) && $formData['accountType'] === 'seller' ? 'checked' : ''; ?>>
        <label class="form-check-label" for="accountSeller">Seller</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="accountType" id="accountBoth" value="both" <?= isset($formData['accountType']) && $formData['accountType'] === 'both' ? 'checked' : ''; ?>>
        <label class="form-check-label" for="accountBoth">Both</label>
      </div>
      <small id="accountTypeHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
    </div>
  </div>

  <div class="form-group row">
    <label for="twoFactorAuth" class="col-sm-2 col-form-label text-right">Two-Factor Authentication</label>
    <div class="col-sm-10">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="twoFactorAuth" name="twoFactorAuth" 
            value="1" <?= isset($formData['twoFactorAuth']) && $formData['twoFactorAuth'] === '1' ? 'checked' : ''; ?>>
        <label class="form-check-label" for="twoFactorAuth">Enable Two-Factor Authentication</label>
      </div>
      <small id="twoFactorAuthHelp" class="form-text text-muted">Adds an extra layer of security to your account.</small>
    </div>
  </div>
  
  <div class="form-group row">
    <label for="preferredCategories" class="col-sm-2 col-form-label text-right">Preferences</label>
    <div class="col-sm-10">
      <select name="preferredCategories[]" id="preferredCategories" class="form-control" multiple>
        <?php foreach ($categories as $category): ?>
          <option value="<?= htmlspecialchars($category['id']) ?>" 
            <?= isset($formData['preferredCategories']) && in_array($category['id'], $formData['preferredCategories']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($category['type']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small id="preferredCategoriesHelp" class="form-text text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
    </div>
  </div>

  <div class="form-group row">
    <label for="username" class="col-sm-2 col-form-label text-right">Username</label>
    <div class="col-sm-10">
      <input type="text" name="username" class="form-control" id="username" 
             value="<?= isset($formData['username']) ? htmlspecialchars($formData['username']) : ''; ?>" 
             placeholder="JohnSmith123">
      <small id="usernameHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
    </div>
  </div>

  <div class="form-group row">
    <label for="email" class="col-sm-2 col-form-label text-right">Email</label>
    <div class="col-sm-10">
      <input type="email" name="email" class="form-control" id="email" 
             value="<?= isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>" 
             placeholder="johnsmith@gmail.com">
      <small id="emailHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
    </div>
  </div>

  <div class="form-group row">
    <label for="password" class="col-sm-2 col-form-label text-right">Password</label>
    <div class="col-sm-10">
      <input type="password" name="password" class="form-control" id="password" placeholder="Password">
      <small id="passwordHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
    </div>
  </div>

  <div class="form-group row">
    <label for="passwordConfirmation" class="col-sm-2 col-form-label text-right">Repeat password</label>
    <div class="col-sm-10">
      <input type="password" name="passwordConfirmation" class="form-control" id="passwordConfirmation" placeholder="Enter password again">
      <small id="passwordConfirmationHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
    </div>
  </div>

  <div class="form-group row">
    <button type="submit" class="btn btn-primary form-control">Register</button>
  </div>
</form>

<div class="text-center">
  Already have an account? <a href="" data-toggle="modal" data-target="#loginModal">Login</a>
</div>

</div>

<?php include_once("footer.php")?>