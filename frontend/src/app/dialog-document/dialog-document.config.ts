import { PdfFile } from '@models/PdfFile';

export class DialogDocumentConfig {
    public document: PdfFile;

    constructor(document: PdfFile) {
        this.document = document;
    }
}
