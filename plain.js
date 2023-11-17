/**
 * Script to handle form actions and send lead to CRM using instructions: https://telegra.ph/API-06-17. 
 * Script is written in vanilla JS, so you can use it on any site without any additional libraries. It uses async/await syntax, so it's not supported by IE11 and older browsers. If you need to support them, you can use Promise syntax instead.
 * It add event listeners to form and send data to CRM using fetch API.
 */ 

// SETUP

const crmEndpoint = 'https://lawtask.pro/API/api.php'
const integrationId = 123456 // replace with your integration ID

const messages = {
	'form_activation': 'Form was activated successfully',
	'form_sent': 'Form sent',
	'form_error': 'Error: ',
	'network_error': 'Network error: ',
}

const elementsToDeactivateIfSuccess = 'input, textarea, button, select'


addEventListeners()

// FUNCTIONS

/**
 * Function to get form from DOM. We don't have access to site and HTML code, so don't know any form fields or attributes. That's why, we have to find them by ourselves and use it carefully.
 * @return {Object} form - The form element.
 * @throws {Error} Form not found
 */
function getForm () {
	const form = document.querySelector( 'form' )
	if ( !form ) {
		throw new Error( 'Form not found' )
	}
	return form
}

/**
 * Adds event listeners to form
 * @return {void}
 */
function addEventListeners () {
	document.addEventListener( 'DOMContentLoaded', function () {
		const form = getForm()
		form.addEventListener( 'submit', onSubmit )
	} )
	console.log( messages.form_activation )
}

/**
 * Handles form submission
 * @param {Object} e - Event object
 * @return {void}
 */
async function onSubmit ( e ) {
	e.preventDefault()

	const formData = new FormData( e.target )
	formData.append( 'integration_id', integrationId )
	// add here any additional data you want to send to CRM

	try {
		const response = await fetch( crmEndpoint, {
			method: 'POST',
			body: formData,
		} )

		if ( !response.ok ) {
			throw new Error( messages.network_error + response.statusText )
		}

		const data = await response.json()
		if ( data?.status !== 'success' ) {
			throw new Error( messages.form_error + data.reason )
		}

		deactivateForm()
	} catch ( error ) {
		displayResult( false, error.message )
	} finally {
		console.log( messages.form_sent )
	}
}

/**
 * Deactivates the form to do not allow to send lead again.
 * @return {void}
 */
function deactivateForm () {
	const form = getForm()
	form.querySelectorAll( elementsToDeactivateIfSuccess ).forEach( element => {
		element.disabled = true
	} )
	form.removeEventListener( 'submit', onSubmit )
	form.reset()
}
