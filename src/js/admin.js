/**
 * Guest Post Plugin Admin JavaScript with React
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import Tooltip from './components/Tooltip';
import SettingsSection from './components/SettingsSection';

document.addEventListener('DOMContentLoaded', function() {
  // Convert tooltips to React
  document.querySelectorAll('.tooltip').forEach((tooltip, index) => {
    const tooltipText = tooltip.getAttribute('title');
    const tooltipId = `tooltip-${index}`;
    
    // Create a container to replace the tooltip
    const container = document.createElement('span');
    container.className = 'tooltip-container';
    tooltip.parentNode.replaceChild(container, tooltip);
    
    const root = createRoot(container);
    root.render(<Tooltip text={tooltipText} id={tooltipId} />);
  });
  
  // Convert settings sections to React
  document.querySelectorAll('.settings-section').forEach((section, index) => {
    const title = section.querySelector('h3').textContent;
    const content = section.querySelector('.settings-section-content').innerHTML;
    const isOpen = section.classList.contains('open');
    
    // Create a container to replace the section
    const container = document.createElement('div');
    container.className = 'react-settings-section';
    container.setAttribute('data-title', title);
    container.setAttribute('data-default-open', isOpen.toString());
    container.innerHTML = content;
    
    section.parentNode.replaceChild(container, section);
    
    const root = createRoot(container);
    root.render(
      <SettingsSection title={title} defaultOpen={isOpen}>
        <div dangerouslySetInnerHTML={{ __html: content }} />
      </SettingsSection>
    );
  });
  
  // Handle conditional display of settings
  const handleToggleVisibility = (toggleId, targetClass, showValue = 'yes') => {
    const toggle = document.getElementById(toggleId);
    if (!toggle) return;
    
    const updateVisibility = () => {
      const elements = document.querySelectorAll(`.${targetClass}`);
      elements.forEach(el => {
        el.style.display = toggle.value === showValue ? 'table-row' : 'none';
      });
    };
    
    toggle.addEventListener('change', updateVisibility);
    updateVisibility(); // Initial state
  };
  
  handleToggleVisibility('enable_mailchimp', 'mailchimp-setting');
  handleToggleVisibility('enable_recaptcha', 'recaptcha-setting');
  handleToggleVisibility('enable_ip_limit', 'ip-limit-setting');
});