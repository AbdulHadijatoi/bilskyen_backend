@extends('layouts.app')

@section('title', 'Sell Your Car - Bilskyen')

@push('styles')
<style>
    /* Expandable Section Styles */
    .expandable-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        margin-bottom: 3rem;
        overflow: hidden;
        transition: all 0.3s ease;
        /* box-shadow: 0 2px 8px oklch(0 0 0 / 0.05); */
    }
    
    .expandable-section:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.05);
    }
    
    .dark .expandable-section:hover {
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.2);
    }
    
    .section-header {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--muted);
        transition: background-color 0.2s;
        user-select: none;
    }
    
    .section-header:hover {
        background: var(--accent);
    }
    
    .section-header.active {
        background: var(--muted);
        color: var(--foreground);
    }
    
    .section-title-group {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        flex: 1;
    }
    
    .section-number {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--background);
        color: var(--foreground);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.625rem;
        flex-shrink: 0;
    }
    
    .section-header.active .section-number {
        background: var(--background);
        color: var(--foreground);
    }
    
    .section-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--foreground);
        line-height: 1.2;
    }
    
    .section-header.active .section-title {
        color: var(--foreground);
    }
    
    .section-subtitle {
        font-size: 0.625rem;
        color: var(--muted-foreground);
        margin-top: 0.125rem;
        line-height: 1.2;
    }
    
    .section-header.active .section-subtitle {
        color: var(--muted-foreground);
        opacity: 1;
    }
    
    .section-icon {
        width: 16px;
        height: 16px;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }
    
    .section-header.active .section-icon {
        transform: rotate(180deg);
    }
    
    .section-content {
        max-height: 5000px;
        overflow: hidden;
        transition: max-height 0.4s ease, padding 0.3s ease;
        padding: 1rem;
    }
    
    .section-content.expanded {
        max-height: 5000px;
        padding: 1rem;
    }
    
    .section-content.collapsed {
        max-height: 0;
        padding: 0 1rem;
    }
    
    .section-description {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    
    /* Required Field Indicator */
    .required-field::after {
        content: ' *';
        color: var(--destructive);
    }
    
    /* Field Help Text */
    .field-help {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }
    
    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.875rem;
    }
    
    @media (min-width: 640px) {
        .form-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 1024px) {
        .form-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    /* Month/Year Field Pair - fields side by side within one grid column */
    .field-pair-inner {
        display: flex;
        gap: 0.5rem;
    }
    
    .field-pair-inner > div {
        flex: 1;
    }
    
    /* On mobile, stack month/year fields vertically */
    @media (max-width: 640px) {
        .field-pair-inner {
            flex-direction: column;
        }
    }
    
    /* License Plate Lookup Section */
    .lookup-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }
    
    .lookup-section:hover {
        box-shadow: 0 4px 16px rgba(0, 74, 173, 0.1);
    }
    
    .dark .lookup-section {
        background: var(--card);
        border-color: var(--border);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    .dark .lookup-section:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 16px rgba(0, 74, 173, 0.2);
    }
    
    .lookup-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--foreground);
        letter-spacing: -0.02em;
    }
    
    .lookup-section p {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    
    .lookup-input-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .lookup-input-wrapper {
        flex: 1;
        min-width: 200px;
        position: relative;
    }
    
    .lookup-input-wrapper label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--muted-foreground);
        margin-bottom: 0.75rem;
        display: block;
    }
    
    .lookup-input-button-container {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }
    
    .lookup-button {
        height: 3.75rem;
        padding: 0 1.5rem;
        background: var(--primary);
        color: var(--primary-foreground);
        border: 1px solid var(--primary);
        border-radius: 0.875rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .lookup-button:hover:not(:disabled) {
        background: var(--primary);
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 74, 173, 0.2);
    }
    
    .lookup-button:active:not(:disabled) {
        transform: translateY(0);
    }
    
    .lookup-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .lookup-button-icon {
        width: 20px;
        height: 20px;
    }
    
    .lookup-button-text {
        display: inline-block;
    }
    
    @media (max-width: 640px) {
        .lookup-input-button-container {
            flex-direction: column;
        }
        
        .lookup-button {
            width: 100%;
            justify-content: center;
        }
        
        .lookup-input-button-container > div {
            width: 100%;
        }
    }
    
    .lookup-input-wrapper input {
        width: 100%;
        height: 3.75rem;
        padding: 0 1.25rem 0 3.75rem;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: var(--background);
        color: var(--foreground);
        border: 1px solid var(--border);
        border-radius: 0.875rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        /* box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 0 0 0 rgba(0, 0, 0, 0); */
        outline: none;
    }
    
    .lookup-input-wrapper input::placeholder {
        color: var(--muted-foreground);
        font-weight: 400;
        text-transform: none;
        letter-spacing: normal;
        opacity: 0.6;
    }
    
    .lookup-input-wrapper input:hover {
        border-color: var(--border);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12), 0 0 0 0 rgba(0, 0, 0, 0);
        transform: translateY(-1px);
    }
    
    .lookup-input-wrapper input:focus {
        border-color: var(--border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 3px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
        background: var(--background);
    }
    
    .dark .lookup-input-wrapper input {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3), 0 0 0 0 rgba(0, 0, 0, 0);
    }
    
    .dark .lookup-input-wrapper input:hover {
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4), 0 0 0 0 rgba(0, 0, 0, 0);
    }
    
    .dark .lookup-input-wrapper input:focus {
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 0 3px rgba(255, 255, 255, 0.05);
    }
    
    .lookup-input-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        width: 22px;
        height: 22px;
        color: var(--muted-foreground);
        pointer-events: none;
        z-index: 1;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .lookup-input-wrapper input:focus ~ .lookup-input-icon {
        color: var(--foreground);
        transform: translateY(-50%) scale(1.15);
    }
    
    .lookup-input-wrapper input:hover ~ .lookup-input-icon {
        color: var(--foreground);
        transform: translateY(-50%) scale(1.05);
    }
    
    .btn {
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    
    .btn-primary {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .btn-primary:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: var(--background);
        color: var(--primary-foreground);
        border: 1px solid var(--primary-foreground);
    }
    
    .btn-secondary:hover:not(:disabled) {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Success Badge */
    .success-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: oklch(0.95 0.1 145);
        border: 1px solid oklch(0.8 0.15 145);
        border-radius: 0.5rem;
        font-size: 0.75rem;
        color: oklch(0.4 0.2 145);
        margin-bottom: 0.75rem;
    }
    
    .dark .success-badge {
        background: oklch(0.3 0.1 145);
        border-color: oklch(0.5 0.15 145);
        color: oklch(0.7 0.2 145);
    }
    
    /* Loading State */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 74, 173, 0.3);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dark .loading-overlay {
        background: rgba(0, 74, 173, 0.5);
    }
    
    .loading-content {
        background: var(--card);
        padding: 1.25rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 10px 25px oklch(0 0 0 / 0.15);
    }
    
    .dark .loading-content {
        box-shadow: 0 10px 25px oklch(0 0 0 / 0.4);
    }
    
    /* Submit Section */
    .submit-section {
        background: var(--muted);
        /* border: 1px solid var(--border); */
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-top: 1.25rem;
        text-align: center;
    }
    
    .submit-section h3 {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .submit-section p {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-bottom: 1rem;
    }
    
    .btn-submit {
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
        background: var(--primary);
        color: var(--primary-foreground);
        border-radius: 0.5rem;
        font-weight: 600;
    }
    
    .btn-submit:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.15);
    }
    
    .dark .btn-submit:hover:not(:disabled) {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.4);
    }
    
    /* Field Error */
    .field-error {
        font-size: 0.75rem;
        color: var(--destructive);
        margin-top: 0.25rem;
    }
    
    /* Expand All Button */
    .expand-controls {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    
    .expand-btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        background: var(--muted);
        color: var(--foreground);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .expand-btn:hover {
        background: var(--accent);
    }
    
    /* Mobile Responsive */
    @media (max-width: 640px) {
        .lookup-section {
            padding: 1rem;
        }
        
        .lookup-input-group {
            flex-direction: column;
        }
        
        .lookup-input-wrapper {
            min-width: 100%;
        }
        
        .section-header {
            padding: 0.5rem 0.625rem;
        }
        
        .section-content.expanded {
            padding: 0.75rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .expand-controls {
            justify-content: stretch;
        }
        
        .expand-btn {
            flex: 1;
        }
    }
    
    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
    
    /* Hide form initially */
    .form-hidden {
        display: none;
    }
    
    .form-visible {
        display: block;
        animation: fadeInForm 0.5s ease-in;
    }
    
    @keyframes fadeInForm {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Equipment Section Styles - Matching vehicles.blade.php */
    .equipment-type-group {
        margin-bottom: 0.5rem;
    }
    
    .equipment-type-toggle {
        cursor: pointer;
    }
    
    .equipment-type-icon {
        transition: transform 0.2s ease;
    }
    
    .equipment-type-icon.rotate-180 {
        transform: rotate(180deg);
    }
    
    .equipment-type-content {
        transition: all 0.3s ease;
    }
    
    /* Image Upload Styles */
    .image-upload-area {
        margin-bottom: 1rem;
    }
    
    .image-upload-area.has-images .upload-dropzone {
        padding: 1rem;
        border-style: solid;
    }
    
    .image-upload-area.has-images .upload-content {
        flex-direction: row;
        gap: 0.75rem;
    }
    
    .image-upload-area.has-images .upload-icon {
        width: 24px;
        height: 24px;
    }
    
    .image-upload-area.has-images .upload-text {
        font-size: 0.75rem;
        margin: 0;
    }
    
    .image-upload-area.has-images .upload-hint {
        display: none;
    }
    
    .image-input {
        position: absolute;
        width: 0;
        height: 0;
        opacity: 0;
        overflow: hidden;
    }
    
    .upload-dropzone {
        border: 2px dashed var(--border);
        border-radius: 0.5rem;
        padding: 2rem 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--muted);
    }
    
    .upload-dropzone:hover {
        border-color: var(--primary);
        background: var(--accent);
    }
    
    .upload-dropzone.drag-over {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground);
    }
    
    .upload-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .upload-icon {
        color: var(--muted-foreground);
        transition: color 0.3s ease;
    }
    
    .upload-dropzone:hover .upload-icon,
    .upload-dropzone.drag-over .upload-icon {
        color: var(--primary);
    }
    
    .upload-dropzone.drag-over .upload-icon {
        color: var(--primary-foreground);
    }
    
    .upload-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
    }
    
    .upload-hint {
        font-size: 0.75rem;
        color: var(--muted-foreground);
    }
    
    .upload-dropzone.drag-over .upload-text,
    .upload-dropzone.drag-over .upload-hint {
        color: var(--primary-foreground);
    }
    
    /* Image Preview Container */
    .image-preview-container {
        margin-top: 1rem;
    }
    
    .image-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
    }
    
    @media (min-width: 640px) {
        .image-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }
    
    @media (min-width: 1024px) {
        .image-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    .image-preview-item {
        position: relative;
        aspect-ratio: 4 / 3;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 2px solid var(--border);
        background: var(--muted);
        transition: all 0.2s ease;
        cursor: move;
        cursor: grab;
    }
    
    .image-preview-item:active {
        cursor: grabbing;
    }
    
    .image-preview-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.1);
    }
    
    .dark .image-preview-item:hover {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.3);
    }
    
    /* Drag and drop states */
    .image-preview-item.dragging {
        opacity: 0.5;
        transform: scale(0.95);
        cursor: grabbing;
        z-index: 1000;
    }
    
    .image-preview-item.drag-over {
        border-color: var(--primary);
        border-width: 3px;
        transform: scale(1.05);
        box-shadow: 0 6px 16px oklch(0 0 0 / 0.15);
    }
    
    .dark .image-preview-item.drag-over {
        box-shadow: 0 6px 16px oklch(0 0 0 / 0.4);
    }
    
    /* Drag handle */
    .image-drag-handle {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 0.25rem;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        z-index: 10;
        pointer-events: none;
    }
    
    .image-preview-item:hover .image-drag-handle {
        opacity: 1;
    }
    
    .image-preview-item.dragging .image-drag-handle {
        opacity: 1;
    }
    
    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    .image-preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0, 74, 173, 0.6) 100%);
        opacity: 0;
        transition: opacity 0.2s ease;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        padding: 0.5rem;
    }
    
    .image-preview-item:hover .image-preview-overlay {
        opacity: 1;
    }
    
    .image-remove-btn {
        background: var(--destructive);
        color: white;
        border: none;
        border-radius: 0.375rem;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.2);
    }
    
    .image-remove-btn:hover {
        background: oklch(0.5 0.25 27);
        transform: scale(1.1);
    }
    
    .image-preview-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 74, 173, 0.7);
        color: white;
        padding: 0.5rem;
        font-size: 0.75rem;
        text-align: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .image-preview-item:hover .image-preview-info {
        opacity: 1;
    }
    
    .image-preview-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--muted-foreground);
    }
    
    /* Error Container - adapts to dark/light mode */
    .error-container {
        border-color: oklch(0.8 0.2 27) !important;
        background: oklch(0.95 0.1 27) !important;
        color: oklch(0.4 0.2 27) !important;
    }
    
    .dark .error-container {
        border-color: oklch(0.6 0.2 27) !important;
        background: oklch(0.3 0.1 27) !important;
        color: oklch(0.7 0.2 27) !important;
    }
    
    /* Override Tailwind red border classes for dark mode */
    .dark .border-red-500 {
        border-color: oklch(0.6 0.25 27) !important;
    }
    
    .dark .border-red-200 {
        border-color: oklch(0.5 0.15 27) !important;
    }
    
    .dark .bg-red-50 {
        background-color: oklch(0.3 0.1 27) !important;
    }
    
    .dark .text-red-800 {
        color: oklch(0.7 0.2 27) !important;
    }
    
    /* Servicebog Radio Buttons Styled as Buttons */
    .servicebog-radio {
        transition: all 0.2s ease;
    }
    
    .servicebog-radio:hover {
        background: var(--accent);
        border-color: var(--primary);
    }
    
    .servicebog-radio input[type="radio"]:checked + span {
        font-weight: 600;
        color: var(--primary);
    }
    
    .servicebog-radio:has(input[type="radio"]:checked) {
        background: var(--primary);
        border-color: var(--primary);
        color: var(--primary-foreground);
    }
    
    .servicebog-radio:has(input[type="radio"]:checked) span {
        color: var(--primary-foreground);
        font-weight: 600;
    }
    
    /* Vehicle Info Display - Compact with Icons */
    .vehicle-info-display {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        padding: 1rem;
        background: var(--muted);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
    }
    
    @media (min-width: 640px) {
        .vehicle-info-display {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    .vehicle-info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: var(--background);
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }
    
    .vehicle-info-item:hover {
        background: var(--accent);
    }
    
    .vehicle-info-icon {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        color: var(--primary);
    }
    
    .vehicle-info-content {
        flex: 1;
        min-width: 0;
    }
    
    .vehicle-info-label {
        font-size: 0.625rem;
        color: var(--muted-foreground);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.125rem;
    }
    
    .vehicle-info-value {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .vehicle-info-value:empty::before {
        content: '—';
        color: var(--muted-foreground);
        font-weight: 400;
    }
    
    /* Plan Cards Styles */
    .plan-card {
        border: 2px solid var(--border);
        border-radius: 0.5rem;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: var(--card);
        position: relative;
    }
    
    .plan-card:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.1);
    }
    
    .plan-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    
    .plan-card:has(input[type="radio"]:checked) {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.15);
    }
    
    .plan-card:has(input[type="radio"]:checked) .plan-name {
        color: var(--primary-foreground);
    }
    
    .plan-card:has(input[type="radio"]:checked) .plan-description,
    .plan-card:has(input[type="radio"]:checked) .plan-features-label,
    .plan-card:has(input[type="radio"]:checked) .plan-feature-item {
        color: var(--primary-foreground);
        opacity: 0.95;
    }
    
    .plan-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }
    
    .plan-description {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-bottom: 0.75rem;
    }
    
    .plan-features-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--muted-foreground);
        margin-bottom: 0.5rem;
    }
    
    .plan-feature-item {
        font-size: 0.75rem;
        color: var(--muted-foreground);
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 1rem;
    }
    
    @media (min-width: 640px) {
        .plans-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 1024px) {
        .plans-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    /* Location Autocomplete Styles */
    .location-autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        margin-top: 0.25rem;
    }
    
    .location-autocomplete-dropdown.show {
        display: block;
    }
    
    .location-autocomplete-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
        border-bottom: 1px solid var(--border);
    }
    
    .location-autocomplete-item:last-child {
        border-bottom: none;
    }
    
    .location-autocomplete-item:hover,
    .location-autocomplete-item.active {
        background: var(--accent);
    }
    
    .location-autocomplete-item-text {
        font-size: 0.875rem;
        color: var(--foreground);
    }
</style>
@endpush

@section('content')
<div class="container py-3 md:py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight mb-2">
            Sell your car on Denmark's largest car market
        </h1>
        <p class="text-muted-foreground">
            Enter your car's license plate and we'll help you with the rest. All fields are visible.
        </p>
    </div>

    @if(session('success'))
        <div class="w-full rounded-md border p-3 mb-4" style="border-color: oklch(0.8 0.15 145); background: oklch(0.95 0.1 145); color: oklch(0.4 0.2 145);">
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
        <style>
            .dark .w-full.rounded-md.border.p-3.mb-4 {
                border-color: oklch(0.5 0.15 145) !important;
                background: oklch(0.3 0.1 145) !important;
                color: oklch(0.7 0.2 145) !important;
            }
        </style>
    @endif

    @if($errors->any())
        <div class="w-full rounded-md border p-3 mb-4" style="border-color: oklch(0.8 0.2 27); background: oklch(0.95 0.1 27); color: oklch(0.4 0.2 27);">
            <p class="text-sm font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <style>
            .dark .w-full.rounded-md.border.p-3.mb-4 {
                border-color: oklch(0.6 0.2 27) !important;
                background: oklch(0.3 0.1 27) !important;
                color: oklch(0.7 0.2 27) !important;
            }
        </style>
    @endif

    <!-- License Plate Lookup Section -->
    <div class="lookup-section bg-gray-50">
        <h2 class="text-lg font-semibold">Find Your Vehicle</h2>
        <p class="text-muted-foreground">Enter your car's license plate number and click Find or press Enter. We'll automatically fill in the vehicle information for you.</p>
        
        <div class="lookup-input-group">
            <div class="lookup-input-wrapper">
                <label for="registration-lookup">
                    License Plate Number
                </label>
                <div class="lookup-input-button-container">
                    <div style="position: relative; flex: 1;">
                        <svg class="lookup-input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            id="registration-lookup"
                            placeholder="Enter license plate (e.g., AB12345)"
                            autocomplete="off"
                            spellcheck="false"
                        />
                    </div>
                    <button type="button" id="lookup-button" class="lookup-button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="lookup-button-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span class="lookup-button-text">Find A Vehicle</span>
                    </button>
                </div>
                <p class="text-xs mt-2" id="lookup-error" style="opacity: 0.8; min-height: 1.25rem;"></p>
            </div>
        </div>
        
        <div id="lookup-loading" class="hidden mt-4">
            <div class="flex items-center gap-2 text-sm" style="opacity: 0.9;">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading vehicle information...</span>
            </div>
        </div>
    </div>

    <!-- Vehicle Form -->
    <form id="vehicle-form" data-action="{{ route('sell-your-car.store') }}" enctype="multipart/form-data" class="form-hidden">
        @csrf

        <!-- Error Display Container -->
        <div id="form-errors-top" class="hidden w-full rounded-md border p-3 mb-4 error-container"></div>

        <!-- Section 1: Basic Vehicle Information -->
        <div class="expandable-section" data-section="basic-info">
            <div class="section-header active" onclick="toggleSection('basic-info')">
                <div class="section-title-group">
                    <div class="section-number">1</div>
                    <div>
                        <div class="section-title">Basic Vehicle Information</div>
                        <div class="section-subtitle">Title, variant, and color</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Basic information about your vehicle.
                </div>
                
                <!-- Vehicle Info Display -->
                <div id="vehicle-info-display" class="vehicle-info-display hidden">
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">Brand</div>
                            <div class="vehicle-info-value" id="vehicle-brand-display"></div>
                        </div>
                    </div>
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">Model</div>
                            <div class="vehicle-info-value" id="vehicle-model-display"></div>
                        </div>
                    </div>
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">Year</div>
                            <div class="vehicle-info-value" id="vehicle-year-display"></div>
                        </div>
                    </div>
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">Fuel Type</div>
                            <div class="vehicle-info-value" id="vehicle-fuel-display"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Title</label>
                        <div id="title-display" class="flex h-9 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm items-center text-muted-foreground">
                            
                    </div>
                        <input type="hidden" id="title" name="title" value="">
                        <p class="field-help">
                            Vehicle title automatically generated from vehicle information.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="variant_id" class="text-sm font-medium">Variant</label>
                        <select id="variant_id" name="variant_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Variant</option>
                            @foreach($lookupData['variants'] as $variant)
                                <option value="{{ $variant->id }}">{{ $variant->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-help">Vehicle variant/trim level</p>
                    </div>

                    <div class="space-y-2">
                        <label for="color_id" class="text-sm font-medium">Color</label>
                        <select id="color_id" name="color_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Color</option>
                            @foreach($lookupData['colors'] as $color)
                                <option value="{{ $color->id }}">{{ $color->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-help">Vehicle exterior color</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Vehicle Specifications -->
        <div class="expandable-section" data-section="specifications">
            <div class="section-header active" onclick="toggleSection('specifications')">
                <div class="section-title-group">
                    <div class="section-number">2</div>
                    <div>
                        <div class="section-title">Vehicle Specifications</div>
                        <div class="section-subtitle">Kilometer driven, registration, inspection, and technical details</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Technical specifications and registration details.
                    </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="km_driven" class="text-sm font-medium required-field">Kilometer Driven</label>
                        <input type="number" id="km_driven" name="km_driven" min="0" required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        <p class="field-help">How far car has driven</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">First Registration</label>
                        <div class="field-pair-inner">
                            <div>
                                <select id="first_registration_month" name="first_registration_month"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Month</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <select id="first_registration_year" name="first_registration_year"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Year</option>
                                    @for($i = date('Y'); $i >= 1900; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p class="field-help">Month and year of first registration</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Last Inspection</label>
                        <div class="field-pair-inner">
                            <div>
                                <select id="last_inspection_month" name="last_inspection_month"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Month</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <select id="last_inspection_year" name="last_inspection_year"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Year</option>
                                    @for($i = date('Y'); $i >= 1900; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p class="field-help">Month and year of last inspection</p>
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_efficiency" id="fuel_efficiency_label" class="text-sm font-medium">KM/L</label>
                        <input type="number" id="fuel_efficiency" name="fuel_efficiency" min="0" step="any" inputmode="decimal"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0.00">
                        <p class="field-help" id="fuel_efficiency_help">Fuel efficiency in kilometers per liter</p>
                    </div>

                    <div class="space-y-2">
                        <label for="technical_total_weight" class="text-sm font-medium">Total Technical Weight (kg)</label>
                        <input type="number" id="technical_total_weight" name="technical_total_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        <p class="field-help">Total technical weight in kg</p>
                    </div>

                    <div class="space-y-2">
                        <label for="euronom_id" class="text-sm font-medium">Euronom</label>
                        <select id="euronom_id" name="euronom_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Euronom</option>
                            @foreach($lookupData['euronorms'] as $euronom)
                                <option value="{{ $euronom->id }}">{{ $euronom->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-help">Euro emission standard</p>
                    </div>
                    </div>
                    </div>
                    </div>

        <!-- Section 3: Equipment & Features -->
        <div class="expandable-section" data-section="equipment">
            <div class="section-header active" onclick="toggleSection('equipment')">
                <div class="section-title-group">
                    <div class="section-number">3</div>
                    <div>
                        <div class="section-title">Equipment & Features</div>
                        <div class="section-subtitle">Select the equipment your vehicle has</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Select the equipment and features your vehicle has. This helps buyers find exactly what they're looking for.
                </div>
                
                <!-- Equipment by Category -->
                <div class="space-y-4">
                    @foreach($lookupData['equipmentTypes'] as $equipmentType)
                        @if($equipmentType->equipments->count() > 0)
                            <div class="equipment-type-group">
                                <h4 class="text-xs font-medium uppercase tracking-wide mb-3 text-muted-foreground">{{ $equipmentType->name }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($equipmentType->equipments as $equipment)
                                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                                            <input 
                                                type="checkbox" 
                                                name="equipment_ids[]" 
                                                value="{{ $equipment->id }}"
                                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                onchange="handleEquipmentChange(this, {{ $equipment->id }}, '{{ addslashes($equipment->name) }}')"
                                            >
                                            <span>{{ $equipment->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    
                    <!-- Equipment without category -->
                    @php
                        $equipmentWithoutType = $lookupData['equipment']->filter(function($equip) {
                            return !$equip->equipment_type_id;
                        });
                    @endphp
                    @if($equipmentWithoutType->count() > 0)
                        <div class="equipment-type-group">
                            <h4 class="text-sm font-semibold uppercase tracking-wide mb-3 text-foreground">Other</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($equipmentWithoutType as $equipment)
                                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                                        <input 
                                            type="checkbox" 
                                            name="equipment_ids[]" 
                                            value="{{ $equipment->id }}"
                                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                            onchange="handleEquipmentChange(this, {{ $equipment->id }}, '{{ addslashes($equipment->name) }}')"
                                        >
                                        <span>{{ $equipment->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

               
                
                <!-- Servicebog -->
                <div class="mb-4">
                    <label class="text-sm font-medium mb-2 block">Servicebog</label>
                    <div class="flex gap-2 md:gap-3">
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="Yes" class="h-3 w-3 md:h-4 md:w-4 text-primary">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="No" class="h-3 w-3 md:h-4 md:w-4 text-primary">
                            <span>No</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="Default" checked class="h-3 w-3 md:h-4 md:w-4 text-primary">
                            <span>Default</span>
                        </label>
                    </div>
                    <p class="field-help mt-2">Does the vehicle have a service book?</p>
                </div>

        <!-- Section 4: Pricing & Tax -->
        <div class="expandable-section" data-section="pricing">
            <div class="section-header active" onclick="toggleSection('pricing')">
                <div class="section-title-group">
                    <div class="section-number">4</div>
                    <div>
                        <div class="section-title">Pricing & Tax</div>
                        <div class="section-subtitle">Price and tax information</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium required-field">Price (DKK)</label>
                        <input type="number" id="price" name="price" required min="0"
                            class="flex h-9 w-full rounded-md border {{ $errors->has('price') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        @error('price')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-help">Selling price in DKK</p>
                    </div>

                    </div>

                <!-- Expandable Tax Information Section -->
                <div class="mt-4 border border-input rounded-lg overflow-hidden">
                    <button type="button" class="equipment-type-toggle w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-foreground hover:bg-accent transition-colors"
                        onclick="toggleTaxInfo()">
                        <span>Tax Information Based on Mileage</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="equipment-type-icon transition-transform" id="tax-info-icon">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                    <div id="tax-info-content" class="equipment-type-content hidden px-4 pb-3 pt-2">
                        <p class="text-sm text-muted-foreground">Tax information based on mileage - To be implemented after consulting with Berken.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Media -->
        <div class="expandable-section" data-section="photos">
            <div class="section-header active" onclick="toggleSection('photos')">
                <div class="section-title-group">
                    <div class="section-number">5</div>
                    <div>
                        <div class="section-title">Photos</div>
                        <div class="section-subtitle">Add photos of your vehicle</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Add photos of your vehicle. Good photos help your listing sell faster! You can select multiple images. Drag and drop or click to upload.
                </div>
                
                <!-- Image Upload Area -->
                <div class="image-upload-area" id="image-upload-area">
                    <input 
                        type="file" 
                        id="images" 
                        name="images[]" 
                        multiple 
                        accept="image/*"
                        class="image-input"
                    >
                    <div class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-content">
                            <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p class="upload-text">Click to upload or drag and drop</p>
                            <p class="upload-hint">PNG, JPG, GIF up to 20MB each</p>
                        </div>
                    </div>
                </div>
                
                <!-- Image Preview Grid -->
                <div id="image-preview-container" class="image-preview-container hidden">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold">
                            Selected Images (<span id="image-count">0</span>)
                        </h4>
                        <button type="button" onclick="clearAllImages()" class="text-xs text-muted-foreground hover:text-foreground">
                            Clear All
                        </button>
                    </div>
                    <div id="image-preview-grid" class="image-preview-grid">
                        <!-- Image previews will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Description -->
        <div class="expandable-section" data-section="description">
            <div class="section-header active" onclick="toggleSection('description')">
                <div class="section-title-group">
                    <div class="section-number">6</div>
                    <div>
                        <div class="section-title">Description</div>
                        <div class="section-subtitle">Vehicle description</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                    <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">Description</label>
                    <textarea id="description" name="description" rows="6"
                        class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Enter vehicle description..."></textarea>
                    <p class="field-help">Describe your vehicle</p>
                </div>
            </div>
                    </div>

        <!-- Section 7: Seller Information -->
        <div class="expandable-section" data-section="seller-info">
            <div class="section-header active" onclick="toggleSection('seller-info')">
                <div class="section-title-group">
                    <div class="section-number">7</div>
                    <div>
                        <div class="section-title">Seller Information</div>
                        <div class="section-subtitle">Your contact details</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="seller_phone" class="text-sm font-medium">Phone</label>
                        <input type="text" id="seller_phone" name="seller_phone" value="{{ $user->phone ?? '' }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Your phone number">
                        <p class="field-help">Your contact phone number</p>
                    </div>

                    <div class="space-y-2">
                        <label for="seller_address" class="text-sm font-medium">Location</label>
                        <div class="relative">
                            <input type="text" id="seller_address" name="seller_address" value="{{ $user->address ?? '' }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Your address">
                            <div id="location-autocomplete" class="location-autocomplete-dropdown"></div>
                        </div>
                        <p class="field-help">Your location</p>
                    </div>

                    <div class="space-y-2">
                        <label for="seller_postcode" class="text-sm font-medium">Postal Code</label>
                        <div class="relative">
                            <input type="text" id="seller_postcode" name="seller_postcode" value="{{ $user->postcode ?? '' }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Postal code">
                            <div id="postcode-autocomplete" class="location-autocomplete-dropdown"></div>
                        </div>
                        <p class="field-help">Your postal code</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 8: Packages -->
        <div class="expandable-section" data-section="packages">
            <div class="section-header active" onclick="toggleSection('packages')">
                <div class="section-title-group">
                    <div class="section-number">8</div>
                    <div>
                        <div class="section-title">Packages</div>
                        <div class="section-subtitle">Select a package for your listing</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Select a package to enhance your vehicle listing. Each package includes different features.
                </div>
                <div class="plans-grid">
                    @foreach($lookupData['plans'] as $plan)
                        <label class="plan-card">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}">
                            <div class="plan-name">{{ $plan->name }}</div>
                            @if($plan->description)
                                <p class="plan-description">{{ $plan->description }}</p>
                            @endif
                            @if($plan->planFeatures && $plan->planFeatures->count() > 0)
                                <div class="mt-2">
                                    <p class="plan-features-label">Features:</p>
                                    <ul class="space-y-1">
                                        @foreach($plan->planFeatures as $planFeature)
                                            <li class="plan-feature-item">• {{ $planFeature->feature->description ?? $planFeature->feature->key }}: {{ $planFeature->value }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Hidden fields for required data from API -->
        <input type="hidden" id="registration" name="registration" value="" required>

        <script>
        function toggleTaxInfo() {
            const content = document.getElementById('tax-info-content');
            const icon = document.getElementById('tax-info-icon');
            if (content && icon) {
                content.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        }
        </script>

        <!-- Submit Section -->
        <div class="submit-section">
            <h3>Ready to publish your listing?</h3>
            <p>Review your information and click the button below to publish your vehicle listing.</p>
            <button type="submit" class="btn btn-submit">
                Publish Vehicle Listing
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    window.locationsData = @json($lookupData['locations'] ?? []);
</script>
<script src="{{ asset('js/sell-your-car-form.js') }}"></script>
@endpush
@endsection
