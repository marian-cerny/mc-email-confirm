<style>

#student-form-wrapper {
	display: none;
	width: 600px;
}

.form-left {
	float: left;
	width: 45%;
}

.form-right {
	float: right;
	width: 45%;
}

.form-bottom {
	clear: both;
	font-size: 65%;
	padding-top: 4ex;
}

.form-bottom-left {
	width: 50%;
	display: inline-block;
}

.form-bottom-submit {
	position: absolute;
	right: 1em;
	bottom: 1ex;
}

.form-left input {
	width: 100%;
	margin-bottom: 2ex;
	padding: 0.7ex;
}

#student-submit {
	width: 150px;
	height: 35px;
	font-size: 140%;
}

.student-form-title {
	padding-bottom: 1ex;
	text-transform: uppercase;
}

.form-right p {
	padding-bottom: 1ex; 
}	
</style>

<div id='student-form-wrapper'>

	<form action="" method="post" class="wpcf7-form student-form">

	<h2 class="student-form-title">Download the 2013 Student Accommodation Report</h2>

		<div class='form-left'>
			<input type='text' placeholder='Name' id='name' name='name' />
			<input type='text' placeholder='Email' id='email' name='email' />
			<input type='text' placeholder='Telephone number' id='phone' name='phone' />
		</div>

		<div class='form-right'>
			<p>
				Please enter your details so we can send the report directly to you via email.
			</p>
			<p>		
				Thank you for your interest in Pinnacle MC Global.
			</p>
		</div>
		
		<div class='form-bottom'>
			<div class='form-bottom-left'>
				By clicking 'Submit' you agree for Pinnacle MC Global Ltd. to store a copy of your information in compliance with UK law. You agree to allow Pinnacle MC Global Ltd to contact you with these details with information relevant to the report.
			</div>
			
			<div class='form-bottom-submit'>
				<input type='submit' class='button-link' id='student-submit' name='submit' value='Submit' />
			</div>
		</div>


	</form>
	
</div>

<?php exit(); ?>