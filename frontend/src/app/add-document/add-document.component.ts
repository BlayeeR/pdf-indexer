import { Component, OnInit } from "@angular/core";
import { MatSliderChange } from "@angular/material/slider";
import { Subject, Observable } from "rxjs";
import { debounceTime } from "rxjs/operators";
import { DocumentService, Type, DocumentConditions } from "../document.service";
import { FileUploadService } from "../file-upload.service";
import { MainService } from "../main.service";
import { PdfFile } from "@models/PdfFile";
import { Vat, Amount } from "@models/Amount";
import { Info } from "@models/Info";
import { Date as PdfDate } from "@models/Date";

@Component({
    selector: "app-add-document",
    templateUrl: "./add-document.component.html",
    styleUrls: ["./add-document.component.scss"],
})
export class AddDocumentComponent implements OnInit {
    public view: number = 0;
    public fileToUpload: File = null;
    public file: PdfFile = null;
    public radiusValueSubject = new Subject<{ Type: Type; Value: number }>();

    constructor(
        public mainService: MainService,
        public documentService: DocumentService,
        private fileUploadService: FileUploadService
    ) {}

    ngOnInit(): void {
        this.radiusValueSubject
            .pipe(debounceTime(300))
            .subscribe((value: { Type: Type; Value: number }) => {
                switch (value.Type) {
                    case Type.Date: {
                        this.documentService
                            .findDates(this.file.Id, value.Value)
                            .subscribe(
                                (
                                    data: PdfFile
                                ) => {
                                    this.documentService.documentConditions.Dates = data.Dates;
                                }
                            );
                        break;
                    }
                    case Type.Amount: {
                        this.documentService
                            .findAmounts(this.file.Id, value.Value)
                            .subscribe(
                                (
                                    data: PdfFile
                                ) => {
                                    this.documentService.documentConditions.Amounts = data.Amounts;
                                }
                            );
                        break;
                    }
                    case Type.Info: {
                        this.documentService
                            .findInfo(this.file.Id, value.Value)
                            .subscribe(
                                (
                                    data: PdfFile
                                ) => {
                                    this.documentService.documentConditions.Infos = data.Infos;
                                }
                            );
                        break;
                    }
                }
            });
    }

    public async nextStep() {
        switch (this.view) {
            case 0: {
                if (this.fileToUpload) {
                    await this.uploadFile();
                        this.documentService
                            .findDates(this.file.Id, 15)
                            .subscribe(
                                (
                                    data: PdfFile
                                ) => {
                                    this.documentService.documentConditions.Dates = data.Dates;
                                    this.view += 1;
                                }
                            );
                }
                break;
            }
            case 1: {
                this.documentService
                    .findAmounts(this.file.Id, 10)
                    .subscribe(
                        (
                            data: PdfFile
                        ) => {
                            this.documentService.documentConditions.Amounts = data.Amounts;
                            this.view += 1;
                        }
                    );

                break;
            }
            case 2: {
                this.documentService
                    .findInfo(this.file.Id, 25)
                    .subscribe(
                        (
                            data: PdfFile
                        ) => {
                            this.documentService.documentConditions.Infos = data.Infos;
                            this.view += 1;
                        }
                    );
                break;
            }
            case 3: {
              this.view += 1;
                break;
            }
            case 4: {
              this.documentService.saveDocument(this.file.Id, this.documentService.documentConditions).toPromise();
            }
        }
    }

    public previousStep(): void {
        this.view -= 1;
    }

    public handleFileInput(files: FileList) {
        this.fileToUpload = files.item(0);
    }

    public async uploadFile(): Promise<PdfFile> {
        const response: PdfFile = await this.fileUploadService
            .postFile(this.fileToUpload)
            .toPromise();
        this.file = response;
        this.documentService.documentConditions = new DocumentConditions();
        this.documentService.documentConditions.Id = this.file.Id;
        this.documentService.documentConditions.Name = this.file.Name;
        return response;
    }

    public onSlide(event: MatSliderChange, type: Type) {
        this.radiusValueSubject.next({ Type: type, Value: event.value });
    }

    public infoValue(info: Info){
        return info.Nearby.filter((v, i)=>info.Selected.includes(i)).reduce((p, c, i, a, )=>p + " " + c, "");
    }
}
