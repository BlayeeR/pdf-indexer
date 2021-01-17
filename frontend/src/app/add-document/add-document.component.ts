import { Component, OnInit } from "@angular/core";
import { MatSliderChange } from "@angular/material/slider";
import { Subject } from "rxjs";
import { debounceTime } from "rxjs/operators";
import { DocumentService, Type } from "../document.service";
import { FileUploadService } from "../file-upload.service";
import { MainService } from "../main.service";
import { PdfFile } from "@models/PdfFile";

@Component({
    selector: "app-add-document",
    templateUrl: "./add-document.component.html",
    styleUrls: ["./add-document.component.scss"],
})
export class AddDocumentComponent implements OnInit {
    public view: number = 0;
    public fileToUpload: File = null;
    public file: PdfFile = null;
    public radiusValueSubject = new Subject<{Type: Type, Value: number}>();

    constructor(
        public mainService: MainService,
        public documentService: DocumentService,
        private fileUploadService: FileUploadService
    ) {}

    ngOnInit(): void {
        this.radiusValueSubject.pipe(debounceTime(300)).subscribe((value: {Type: Type, Value: number}) => {
          switch(value.Type) {
            case Type.Date: {
              this.documentService.findDates(this.file.Id, value.Value).subscribe((data: PdfFile)=> {
                this.file = data;
                this.documentService.setDocumentConditions(data);
              });
              break;
            }
            case Type.Amount: {
              this.documentService.findAmounts(this.file.Id, value.Value).subscribe((data: PdfFile)=> {
                this.file = data;
                this.documentService.setDocumentConditions(data);
              });
              break;
            }
            case Type.Info: {
              this.documentService.findInfo(this.file.Id, value.Value).subscribe((data: PdfFile)=> {
                this.file = data;
                this.documentService.setDocumentConditions(data);
              });
              break;
            }
          }
        });
    }

    public nextStep(): void {
        switch (this.view) {
            case 0: {
                if (this.fileToUpload) {
                    this.uploadFile();
                }
                break;
            }
            case 1: {
              this.documentService.findAmounts(this.file.Id, 10).subscribe((data: PdfFile) => {
                this.documentService.setDocumentConditions(data);
                this.view += 1;
              });
                break;
            }
            case 2: {
              this.documentService.findInfo(this.file.Id, 25).subscribe((data: PdfFile) => {
                this.documentService.setDocumentConditions(data);
                this.view += 1;
              });
              break;
            }
            case 3: {
             this.documentService.selectInfo(this.file.Id, this.documentService.documentConditions).subscribe(()=> {
              this.view += 1;
             });
             break;
            }
        }
    }

    public previousStep(): void {
        this.view -= 1;
    }

    public handleFileInput(files: FileList) {
        this.fileToUpload = files.item(0);
    }

    public uploadFile() {
        this.fileUploadService
            .postFile(this.fileToUpload)
            .subscribe((data: PdfFile) => {
                this.file = data;
                this.documentService.setDocumentConditions(data);
                this.view += 1;
            });
    }

    public onSlide(event: MatSliderChange, type: Type) {
        this.radiusValueSubject.next({Type: type, Value: event.value});
    }
}
