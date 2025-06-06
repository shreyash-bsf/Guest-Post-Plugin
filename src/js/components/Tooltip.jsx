import React from 'react';
import { Tooltip as ForceTooltip } from '@bsf/force-ui';

const Tooltip = ({ text, id }) => {
  return (
    <ForceTooltip
      content={text}
      position="top"
      id={id}
    >
      <span className="inline-block w-4 h-4 bg-gray-200 text-gray-700 rounded-full text-center leading-4 text-xs ml-1 cursor-help">
        ?
      </span>
    </ForceTooltip>
  );
};

export default Tooltip;