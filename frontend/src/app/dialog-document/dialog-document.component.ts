import { Component, OnInit, Inject, HostBinding } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';
import { DialogDocumentConfig } from './dialog-document.config';
import { PdfFile } from '@models/PdfFile';
import { DocumentService } from 'app/document.service';
import { environment } from 'environments/environment';

@Component({
  selector: 'app-dialog-document',
  templateUrl: './dialog-document.component.html',
  styleUrls: ['./dialog-document.component.scss']
})
export class DialogDocumentComponent implements OnInit {

  constructor(@Inject(MAT_DIALOG_DATA) public data, public documentService: DocumentService) { }

  public document: PdfFile;
  public url: string;
  public tab: number = 0;

  ngOnInit(): void {
    if(this.data && this.data instanceof DialogDocumentConfig) {
      this.document = this.data.document;
    }

    this.url = environment.apiUrl + "/files/getfile?id=" + this.document.Id;
  }
}
