<?php
require __DIR__ . '/../src/bootstrap.php';
handle_booking($db, $serviceRepo);
[$flash, $old] = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Beauty Salon Management System</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">

	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

	<link rel="stylesheet" href="css/animate.css">
	
	<link rel="stylesheet" href="css/owl.carousel.min.css">
	<link rel="stylesheet" href="css/owl.theme.default.min.css">
	<link rel="stylesheet" href="css/magnific-popup.css">

	<link rel="stylesheet" href="css/bootstrap-datepicker.css">
	<link rel="stylesheet" href="css/jquery.timepicker.css">

	
	<link rel="stylesheet" href="css/flaticon.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<?php include __DIR__ . '/../templates/header.php' ?>
	<!-- END nav -->
	
	<section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg_1.png');" data-stellar-background-ratio="0.5">
		<div class="overlay"></div>
		<div class="container">
			<div class="row no-gutters slider-text align-items-end justify-content-center">
				<div class="col-md-9 ftco-animate text-center mb-5">
					<h1 class="mb-2 bread">Book Service Now</h1>
					<p class="breadcrumbs"><span class="mr-2"><a href="index.php">Home <i class="fa fa-chevron-right"></i></a></span> <span>Services <i class="fa fa-chevron-right"></i></span></p>
				</div>
			</div>
		</div>
	</section>

	<section class="ftco-section ftco-wrap-about ftco-no-pb ftco-no-pt">
		<div class="container">
			<div class="row no-gutters">
				<div class="col-sm-12 p-4 p-md-5 d-flex align-items-center justify-content-center bg-primary">
					<form action="" method="post" class="appointment-form" id="book">
						<?php include __DIR__ . '/../templates/flash.php' ?>
						<?= \Salon\Csrf::field() ?>
						<h3 class="mb-3">Book your Service</h3>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<input type="text" name="name" class="form-control" placeholder="Name" value="<?= e($old['name'] ?? '') ?>" required>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<input type="email" name="email" class="form-control" placeholder="Email" value="<?= e($old['email'] ?? '') ?>" required>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<input type="tel" name="phone" class="form-control" placeholder="Phone" value="<?= e($old['phone'] ?? '') ?>" required>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<div class="input-wrap">
										<div class="icon"><span class="fa fa-calendar"></span></div>
										<input type="text" name="adate" class="form-control book_date" placeholder="Date" autocomplete="off" value="<?= e($old['adate'] ?? '') ?>" required>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<div class="input-wrap">
										<div class="icon"><span class="fa fa-clock-o"></span></div>
										<input type="text" name="atime" class="form-control book_time" placeholder="Time" autocomplete="off" value="<?= e($old['atime'] ?? '') ?>" required>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<div class="form-field">
										<div class="select-wrap">
											<div class="icon"><span class="fa fa-chevron-down"></span></div>
											<select name="service_id" id="services" class="form-control" required>
<?php include __DIR__ . '/../templates/service_options.php' ?>
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-2">
								<div class="form-group">
									<input type="submit" name="book" value="Book Now" class="btn btn-white ">
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</section>

	<section class="ftco-section">
		<div class="container">
			<div class="row d-flex">
				<div class="col-md-12 ftco-animate makereservation ">
					<h1 class="mb-2 bread">Services</h1>
					<table class="table table-bordered"> 
						<thead> 
							<tr> 
								<th>#</th>
								<th>Service Name</th>
								<th>Service Price</th> 
							</tr>
						</thead>
						<tbody>
							<?php foreach ($services as $i => $service): ?>
								<tr><th scope="row"><?= $i + 1 ?></th><td><?= e($service['name']) ?></td><td>$<?= e($service['cost']) ?></td></tr>
							<?php endforeach; ?>
						</tbody> 
					</table> 
				</div>
			</div>
		</div>
	</section>
	<?php include __DIR__ . '/../templates/footer.php' ?>
	<!-- loader -->
	<div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>


	<script src="js/jquery.min.js"></script>
	<script src="js/jquery-migrate-3.0.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery.easing.1.3.js"></script>
	<script src="js/jquery.waypoints.min.js"></script>
	<script src="js/jquery.stellar.min.js"></script>
	<script src="js/owl.carousel.min.js"></script>
	<script src="js/jquery.magnific-popup.min.js"></script>
	<script src="js/jquery.animateNumber.min.js"></script>
	<script src="js/bootstrap-datepicker.js"></script>
	<script src="js/jquery.timepicker.min.js"></script>
	<script src="js/scrollax.min.js"></script>
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&sensor=false"></script>
	<script src="js/google-map.js"></script>
	<script src="js/main.js"></script>

</body>
</html>