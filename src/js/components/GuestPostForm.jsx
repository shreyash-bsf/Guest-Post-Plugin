import React, { useState, useEffect } from 'react';
import { 
  Button, 
  Input, 
  TextArea, 
  Checkbox, 
  Dropzone 
} from '@bsf/force-ui';

const GuestPostForm = ({ ajaxUrl, nonce, labels, options }) => {
  const [formData, setFormData] = useState({
    title: '',
    authorName: '',
    authorEmail: '',
    authorBio: '',
    subscribeNewsletter: options.newsletterDefault === 'yes'
  });
  
  const [featuredImage, setFeaturedImage] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [response, setResponse] = useState({ type: '', message: '' });
  const [isDarkMode, setIsDarkMode] = useState(false);
  
  // Check if dark mode is enabled
  useEffect(() => {
    const container = document.getElementById('guest-post-form-container');
    if (container && container.classList.contains('guest-post-form-dark')) {
      setIsDarkMode(true);
    }
  }, []);
  
  // Initialize WordPress editor after component mounts
  useEffect(() => {
    // Create a textarea for the editor
    const editorContainer = document.getElementById('wp-editor-container');
    if (editorContainer) {
      const textarea = document.createElement('textarea');
      textarea.id = 'post-content';
      textarea.name = 'post-content';
      textarea.required = true;
      textarea.placeholder = 'Write your post content here...';
      editorContainer.appendChild(textarea);
      
      // Initialize WordPress editor if available
      if (window.wp && window.wp.editor) {
        window.wp.editor.initialize('post-content', {
          tinymce: {
            wpautop: true,
            plugins: 'lists,paste,tabfocus,wplink,wordpress',
            toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
            setup: function(editor) {
              editor.on('change', function() {
                editor.save();
              });
              
              editor.on('init', function() {
                // Apply dark mode styles if needed
                if (isDarkMode) {
                  const iframe = document.getElementById('post-content_ifr');
                  if (iframe) {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    const style = iframeDoc.createElement('style');
                    style.textContent = `
                      body {
                        background-color: #1f2937 !important;
                        color: #f9f9f9 !important;
                        padding: 10px !important;
                      }
                      p, h1, h2, h3, h4, h5, h6, li, td, th {
                        color: #f9f9f9 !important;
                      }
                      a {
                        color: #60a5fa !important;
                      }
                      * {
                        border-color: #4b5563 !important;
                      }
                      pre {
                        background-color: #374151 !important;
                        border: 1px solid #4b5563 !important;
                        padding: 10px !important;
                      }
                      code {
                        background-color: #374151 !important;
                        color: #f9f9f9 !important;
                      }
                      blockquote {
                        border-left: 4px solid #4b5563 !important;
                        padding-left: 16px !important;
                        color: #d1d5db !important;
                      }
                    `;
                    iframeDoc.head.appendChild(style);
                  }
                  
                  // Style the editor toolbar
                  const editorContainer = document.querySelector('.mce-container');
                  if (editorContainer) {
                    editorContainer.style.backgroundColor = '#444';
                    editorContainer.style.border = '1px solid #555';
                  }
                }
              });
            }
          },
          quicktags: true
        });
      }
    }
    
    // Cleanup function
    return () => {
      if (window.wp && window.wp.editor && window.wp.editor.remove) {
        window.wp.editor.remove('post-content');
      }
    };
  }, []);
  
  const handleChange = (name, value) => {
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };
  
  const handleFileChange = (files) => {
    if (files && files.length > 0) {
      setFeaturedImage(files[0]);
    }
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setResponse({ type: '', message: 'Processing your submission...' });
    
    // Get content from TinyMCE if available
    let content = '';
    if (window.tinyMCE && window.tinyMCE.get('post-content')) {
      content = window.tinyMCE.get('post-content').getContent();
    } else {
      const contentField = document.getElementById('post-content');
      content = contentField ? contentField.value : '';
    }
    
    const data = new FormData();
    data.append('action', 'submit_guest_post');
    data.append('guest_post_nonce', nonce);
    data.append('post-title', formData.title);
    data.append('post-content', content);
    data.append('author-name', formData.authorName);
    data.append('author-email', formData.authorEmail);
    data.append('author-bio', formData.authorBio);
    
    if (formData.subscribeNewsletter) {
      data.append('subscribe_newsletter', 'yes');
    }
    
    if (featuredImage) {
      data.append('featured-image', featuredImage);
    }
    
    // Add honeypot field if enabled
    if (options.enableHoneypot === 'yes') {
      data.append('website', '');
    }
    
    try {
      const res = await fetch(ajaxUrl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
      });
      
      const result = await res.json();
      
      if (result.success) {
        setResponse({ type: 'success', message: result.data });
        setFormData({
          title: '',
          authorName: '',
          authorEmail: '',
          authorBio: '',
          subscribeNewsletter: options.newsletterDefault === 'yes'
        });
        setFeaturedImage(null);
        
        // Reset file input
        const fileInput = document.getElementById('featured-image');
        if (fileInput) fileInput.value = '';
        
        // Reset TinyMCE if it exists
        if (window.tinyMCE && window.tinyMCE.get('post-content')) {
          window.tinyMCE.get('post-content').setContent('');
        }
      } else {
        setResponse({ type: 'error', message: result.data });
      }
    } catch (error) {
      setResponse({ type: 'error', message: 'An error occurred. Please try again later.' });
    }
    
    setIsSubmitting(false);
  };
  
  return (
    <div className={isDarkMode ? "force-ui-dark" : "force-ui-light"}>
      <form onSubmit={handleSubmit} className={`guest-post-form ${isDarkMode ? 'guest-post-form-dark' : ''}`}>
        <div className="mb-5">
          <label htmlFor="title" className="block mb-1 font-bold">
            {labels.title} <span className="text-red-600" aria-hidden="true">*</span>
            <span className="screen-reader-text">(required)</span>
          </label>
          <Input
            id="title"
            name="title"
            value={formData.title}
            onChange={(value) => handleChange('title', value)}
            required
            aria-required="true"
            placeholder="Enter your post title"
            size="md"
          />
        </div>
        
        <div className="mb-5">
          <label htmlFor="post-content" className="block mb-1 font-bold">
            {labels.content} <span className="text-red-600" aria-hidden="true">*</span>
            <span className="screen-reader-text">(required)</span>
          </label>
          <div id="wp-editor-container"></div>
        </div>
        
        <div className="mb-5">
          <label htmlFor="authorName" className="block mb-1 font-bold">
            {labels.authorName} <span className="text-red-600" aria-hidden="true">*</span>
            <span className="screen-reader-text">(required)</span>
          </label>
          <Input
            id="authorName"
            name="authorName"
            value={formData.authorName}
            onChange={(value) => handleChange('authorName', value)}
            required
            aria-required="true"
            placeholder="Enter your full name"
            size="md"
          />
        </div>
        
        <div className="mb-5">
          <label htmlFor="authorEmail" className="block mb-1 font-bold">
            {labels.authorEmail} <span className="text-red-600" aria-hidden="true">*</span>
            <span className="screen-reader-text">(required)</span>
          </label>
          <Input
            id="authorEmail"
            name="authorEmail"
            type="email"
            value={formData.authorEmail}
            onChange={(value) => handleChange('authorEmail', value)}
            required
            aria-required="true"
            placeholder="Enter your email address"
            size="md"
          />
        </div>
        
        <div className="mb-5">
          <label htmlFor="authorBio" className="block mb-1 font-bold">
            {labels.authorBio} <span className="text-red-600" aria-hidden="true">*</span>
            <span className="screen-reader-text">(required)</span>
          </label>
          <TextArea
            id="authorBio"
            name="authorBio"
            value={formData.authorBio}
            onChange={(value) => handleChange('authorBio', value)}
            rows={3}
            required
            aria-required="true"
            placeholder="Write a short bio about yourself"
            size="md"
          />
        </div>
        
        {options.enableMailchimp === 'yes' && (
          <div className="mb-5">
           <Checkbox
            id="subscribeNewsletter"
            name="subscribeNewsletter"
            checked={formData.subscribeNewsletter}
            onChange={(checked) => handleChange('subscribeNewsletter', checked)}
            label={{
              heading: options.newsletterLabel
            }}
            size="md"
          />
          </div>
        )}
        
        <div className="mb-5">
          <label htmlFor="featured-image" className="block mb-1 font-bold">
            {labels.featuredImage}
          </label>
          <Dropzone
            id="featured-image"
            name="featured-image"
            onChange={handleFileChange}
            accept="image/*"
            aria-describedby="featured-image-desc"
            size="md"
          />
          <p id="featured-image-desc" className="text-sm mt-1">
            {labels.imageDescription}
          </p>
        </div>
        
        {options.enableRecaptcha === 'yes' && options.recaptchaSiteKey && (
          <div className="mb-5">
            <div className="g-recaptcha" data-sitekey={options.recaptchaSiteKey}></div>
          </div>
        )}
        
        <Button
          type="submit"
          disabled={isSubmitting}
          variant={isDarkMode ? "secondary" : "primary"}
          size="md"
          className="submit-button"
        >
          {isSubmitting ? labels.submitting : labels.submit}
        </Button>
      </form>
      
      {response.message && (
        <div
          id="form-response"
          role="status"
          aria-live="polite"
          className={response.type === 'success' ? 'success-message' : 'error-message'}
        >
          {response.message}
        </div>
      )}
    </div>
  );
};

export default GuestPostForm;