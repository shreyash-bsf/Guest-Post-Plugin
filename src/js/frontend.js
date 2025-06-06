/**
 * Guest Post Plugin Frontend JavaScript with React
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import GuestPostForm from './components/GuestPostForm';

document.addEventListener('DOMContentLoaded', function() {
  const formContainer = document.getElementById('guest-post-form-container');
  
  if (formContainer) {
    const ajaxUrl = formContainer.getAttribute('data-ajax-url');
    const nonce = formContainer.getAttribute('data-nonce');
    
    // Get labels from data attributes
    const labels = {
      title: formContainer.getAttribute('data-label-title') || 'Post Title',
      content: formContainer.getAttribute('data-label-content') || 'Post Content',
      authorName: formContainer.getAttribute('data-label-name') || 'Your Name',
      authorEmail: formContainer.getAttribute('data-label-email') || 'Your Email',
      authorBio: formContainer.getAttribute('data-label-bio') || 'Short Bio',
      featuredImage: formContainer.getAttribute('data-label-image') || 'Featured Image',
      imageDescription: formContainer.getAttribute('data-label-image-desc') || 'Recommended size: 1200x628 pixels',
      submit: formContainer.getAttribute('data-label-submit') || 'Submit Guest Post',
      submitting: formContainer.getAttribute('data-label-submitting') || 'Submitting...'
    };
    
    // Get options from data attributes
    const options = {
      enableMailchimp: formContainer.getAttribute('data-enable-mailchimp') || 'no',
      newsletterLabel: formContainer.getAttribute('data-newsletter-label') || 'Subscribe to our newsletter',
      newsletterDefault: formContainer.getAttribute('data-newsletter-default') || 'no',
      enableHoneypot: formContainer.getAttribute('data-enable-honeypot') || 'no',
      enableRecaptcha: formContainer.getAttribute('data-enable-recaptcha') || 'no',
      recaptchaSiteKey: formContainer.getAttribute('data-recaptcha-site-key') || ''
    };
    
    try {
      const root = createRoot(formContainer);
      root.render(
        <GuestPostForm 
          ajaxUrl={ajaxUrl}
          nonce={nonce}
          labels={labels}
          options={options}
        />
      );
    } catch (error) {
      console.error('Error rendering React component:', error);
      
      // Fallback to traditional form if React fails
      formContainer.innerHTML = `
        <div class="max-w-3xl mx-auto p-5 bg-gray-50 rounded-lg shadow-md w-full">
          <form id="guest-post-form" method="post" enctype="multipart/form-data" role="form" aria-label="Guest post submission form">
            <div class="mb-5">
              <label for="post-title" class="block mb-1 font-bold">${labels.title} <span class="text-red-600" aria-hidden="true">*</span></label>
              <input type="text" id="post-title" name="post-title" required aria-required="true" placeholder="Enter your post title" class="w-full p-2.5 border border-gray-300 rounded-md min-h-[40px]">
            </div>
            
            <div class="mb-5">
              <label for="post-content" class="block mb-1 font-bold">${labels.content} <span class="text-red-600" aria-hidden="true">*</span></label>
              <textarea id="post-content" name="post-content" rows="10" required aria-required="true" placeholder="Write your post content here..." class="w-full p-2.5 border border-gray-300 rounded-md min-h-[150px]"></textarea>
            </div>
            
            <div class="mb-5">
              <label for="author-name" class="block mb-1 font-bold">${labels.authorName} <span class="text-red-600" aria-hidden="true">*</span></label>
              <input type="text" id="author-name" name="author-name" required aria-required="true" placeholder="Enter your full name" class="w-full p-2.5 border border-gray-300 rounded-md min-h-[40px]">
            </div>
            
            <div class="mb-5">
              <label for="author-email" class="block mb-1 font-bold">${labels.authorEmail} <span class="text-red-600" aria-hidden="true">*</span></label>
              <input type="email" id="author-email" name="author-email" required aria-required="true" placeholder="Enter your email address" class="w-full p-2.5 border border-gray-300 rounded-md min-h-[40px]">
            </div>
            
            <div class="mb-5">
              <label for="author-bio" class="block mb-1 font-bold">${labels.authorBio} <span class="text-red-600" aria-hidden="true">*</span></label>
              <textarea id="author-bio" name="author-bio" rows="3" required aria-required="true" placeholder="Write a short bio about yourself" class="w-full p-2.5 border border-gray-300 rounded-md min-h-[150px]"></textarea>
            </div>
            
            <div class="mb-5">
              <label for="featured-image" class="block mb-1 font-bold">${labels.featuredImage}</label>
              <input type="file" id="featured-image" name="featured-image" accept="image/*" aria-label="Upload featured image" class="w-full py-2.5">
              <p class="text-sm text-gray-600 mt-1">${labels.imageDescription}</p>
            </div>
            
            <input type="hidden" name="action" value="submit_guest_post">
            <input type="hidden" name="guest_post_nonce" value="${nonce}">
            
            <button type="submit" aria-label="Submit guest post" class="bg-blue-600 text-white border-0 py-3 px-5 text-base rounded cursor-pointer transition-colors hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 w-full md:max-w-[200px]">${labels.submit}</button>
          </form>
          <div id="form-response" role="status" aria-live="polite" class="mt-5 p-2.5 rounded w-full"></div>
        </div>
      `;
      
      // Initialize form submission with AJAX
      const form = document.getElementById('guest-post-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const formData = new FormData(this);
          const responseDiv = document.getElementById('form-response');
          
          responseDiv.textContent = 'Processing your submission...';
          responseDiv.className = 'mt-5 p-2.5 rounded w-full';
          
          fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
          })
          .then(response => response.json())
          .then(result => {
            if (result.success) {
              responseDiv.textContent = result.data;
              responseDiv.className = 'mt-5 p-2.5 rounded w-full bg-green-100 text-green-800 border border-green-200';
              form.reset();
            } else {
              responseDiv.textContent = result.data;
              responseDiv.className = 'mt-5 p-2.5 rounded w-full bg-red-100 text-red-800 border border-red-200';
            }
          })
          .catch(error => {
            responseDiv.textContent = 'An error occurred. Please try again later.';
            responseDiv.className = 'mt-5 p-2.5 rounded w-full bg-red-100 text-red-800 border border-red-200';
          });
        });
      }
    }
  }
});