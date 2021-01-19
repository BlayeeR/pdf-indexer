import { Component, OnInit } from '@angular/core';
import { DocumentService } from 'app/document.service';
import { PdfFile } from '@models/PdfFile';

@Component({
  selector: 'app-documents',
  templateUrl: './documents.component.html',
  styleUrls: ['./documents.component.scss']
})
export class DocumentsComponent implements OnInit {

  constructor(public data: DocumentService) { }

  ngOnInit(): void {
    this.data.getDocuments();
  }

  public async deleteDocument(document: PdfFile, index: number) {
    await this.data.deleteDocument(document.Id).toPromise();
    this.data.documents.splice(index, 1);
  }

}
