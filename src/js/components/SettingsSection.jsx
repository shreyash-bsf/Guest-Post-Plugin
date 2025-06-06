import React, { useState } from 'react';

const SettingsSection = ({ title, children, defaultOpen = false }) => {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  
  const toggleSection = (e) => {
    if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
      return;
    }
    
    if (e.type === 'keydown') {
      e.preventDefault();
    }
    
    setIsOpen(!isOpen);
  };
  
  return (
    <div className={`settings-section ${isOpen ? 'open' : 'closed'}`}>
      <h3 
        tabIndex="0"
        role="button"
        aria-expanded={isOpen}
        onClick={toggleSection}
        onKeyDown={toggleSection}
      >
        {title}
      </h3>
      <div className="settings-section-content">
        {children}
      </div>
    </div>
  );
};

export default SettingsSection;