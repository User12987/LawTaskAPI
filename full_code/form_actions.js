/**
 * Plain JS form actions
 * API instruction: https://telegra.ph/API-06-17
 */

// SETUP
const form = document.getElementById( 'leadForm' )
const resultElement = document.getElementById( 'result' )

const messages = {
	'form_sent': 'Form sent successfully',
	'form_error': 'Error: ',
	'form_invalid': 'Form is invalid and cannot be sent',
	'network_error': 'Network error: ',
	'required_field': 'Please fill in this field',
	'invalid_phone': 'Phone number should contain from 5 to 15 digits',
}

const classes = {
	'error': 'error',
	'success': 'success',
	'hidden': 'hidden',
	'spinner': 'spinner',
}

const elementsForDeactivation = 'input, textarea, button, select'
const hintPostfix = 'Hint'


if ( !form || !resultElement ) {
	throw new Error( 'Form or result element not found' )
}

addEventListeners()
console.log( 'Form was activated successfully' )

// FUNCTIONS

/**
 * Adds event listeners to form
 * @return {void}
 */
function addEventListeners () {
	form.addEventListener( 'submit', onSubmit )
}

/**
 * Handles form submission
 * @param {Object} e - Event object
 * @return {void}
 */
async function onSubmit ( e ) {
	e.preventDefault()

	const isValid = validateForm( {
		'name': form.name,
		'phone': form.phone,
		'situation': form.situation,
		'city': form.city,
	} )
	if ( !isValid ) {
		displayResult( false, messages.form_error + messages.form_invalid )
		return
	}


	displayLoading( true )

	const formData = new FormData( e.target )
	try {
		const response = await fetch( e.target.action, {
			method: 'POST',
			body: formData,
		} )

		if ( !response.ok ) {
			throw new Error( messages.network_error + response.statusText )
		}

		const data = await response.json()
		if ( data?.status !== 'success' ) {
			displayResult( false, messages.form_error + data.reason )
			return
		}

		displayResult( true, messages.form_sent )
		deactivateForm( form )
	} catch ( error ) {
		displayResult( false, error.message )
	} finally {
		displayLoading( false )
	}
}

/**
 * Displays a result message.
 * @param {Boolean} result - Whether the form was sent successfully.
 * @param {string} text - The message to display.
 * @return {void}
 */
function displayResult ( result, text ) {
	resultElement.innerText = text
	resultElement.classList.remove( classes.hidden )
	resultElement.classList.add( result ? classes.success : classes.error )
}

/**
 * Disables all form elements.
 * @param {Object} form - The form to disable.
 * @return {void}
 */
function deactivateForm ( form ) {
	form.querySelectorAll( elementsForDeactivation ).forEach( element => {
		element.disabled = true
		element.classList.remove( classes.error )
		element.classList.remove( classes.success )
	} )

	form.removeEventListener( 'submit', onSubmit )
	form.reset()
}

/**
 * Show or hide loading indicator.
 * @param {boolean} show - Whether to show the loading indicator.
 * @return {void}
 */
function displayLoading ( show ) {
	const spinner = document.getElementById( classes.spinner )
	if ( show ) {
		spinner.classList.remove( classes.hidden )
	} else {
		spinner.classList.add( classes.hidden )
	}
}

/**
 * Function to validate form fields.
 * @param {Object} fields - Object containing form fields
 * @return {boolean} - Whether the form is valid
 */
function validateForm ( fields ) {
	let isValid = true

	// non empty fields are invalid
	Object.keys( fields ).forEach( field => {
		if ( !fields[ field ].value ) {
			displayFieldState( fields[ field ], false )
			isValid = false
		} else {
			displayFieldState( fields[ field ], true )
		}
	} )

	// phone number should be possible phone number
	if ( fields.phone?.value?.length < 5 || fields.phone?.value?.length > 15 ) {
		displayFieldState( fields.phone, false, messages.invalid_phone )
		isValid = false
	}

	return isValid
}

/**
 * Display field state (valid or invalid)
 * @param {Object} field - Field to be validated
 * @param {boolean} isValid - Whether the field is valid
 * @param {string} hintText - Hint text to be displayed
 * @return {void}
 */
function displayFieldState ( field, isValid, hintText ) {
	const hintId = field.name + hintPostfix
	const hintElement = document.getElementById( hintId )
	console.log( hintElement )
	field.setAttribute( 'aria-invalid', isValid )

	if ( isValid ) {
		field.classList.remove( classes.error )
		field.classList.add( classes.success )
		if ( hintElement ) hintElement.classList.add( classes.hidden )
		return
	}
	field.classList.remove( classes.success )
	field.classList.add( classes.error )
	if ( hintElement ) {
		hintElement.classList.remove( classes.hidden )
		hintElement.textContent = hintText || messages.required_field
		console.log( hintElement )
	}
}
