export type ProfileDocumentItem = {
    id: number;
    category: 'resume' | 'license' | 'certification';
    original_name: string;
    mime_type: string;
    size: number;
    url: string;
};


export type ProfilePreviewInfo = {
    width: number;
    height: number;
    pages?: number;
};
